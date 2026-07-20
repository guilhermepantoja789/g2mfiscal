<?php

namespace App\Services\Fiscal;

use App\Contracts\FiscalIssuerInterface;
use App\Core\FiscalEngine\Certificates\A1Manager;
use App\Core\FiscalEngine\Dto\NfceEmitData;
use App\Core\FiscalEngine\Exceptions\SefazRejectionException;
use App\Core\FiscalEngine\Security\QrCodeGenerator;
use App\Core\FiscalEngine\Security\XmlDsigSigner;
use App\Core\FiscalEngine\Transport\SefazEndpoints;
use App\Core\FiscalEngine\Transport\SefazSoapClient;
use App\Core\FiscalEngine\Xml\NfceXmlBuilder;
use App\Models\Empresa;
use App\Models\Nfce;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RawNativeNfceIssuer implements FiscalIssuerInterface
{
    public function __construct(
        private readonly A1Manager $a1Manager = new A1Manager,
        private readonly NfceXmlBuilder $xmlBuilder = new NfceXmlBuilder,
        private readonly XmlDsigSigner $signer = new XmlDsigSigner,
        private readonly ?QrCodeGenerator $qrCodeGenerator = null,
        private readonly SefazEndpoints $endpoints = new SefazEndpoints,
        private readonly ?SefazSoapClient $soapClient = null,
    ) {}

    private function qr(): QrCodeGenerator
    {
        return $this->qrCodeGenerator ?? new QrCodeGenerator((string) config('nfce.qrcode_version', '2'));
    }

    private function soap(): SefazSoapClient
    {
        return $this->soapClient ?? new SefazSoapClient(
            $this->endpoints,
            (int) config('nfce.soap.timeout', 60),
            (int) config('nfce.soap.connect_timeout', 20),
        );
    }

    public function emit(Empresa $empresa, NfceEmitRequest $request, ?Nfce $nfce = null): NfceEmitResult
    {
        $this->assertEmpresaPronta($empresa);

        $certificado = $empresa->certificado;
        if (! $certificado) {
            throw new InvalidArgumentException('Empresa sem certificado A1 ativo.');
        }

        $a1 = $this->a1Manager->loadFromModel($certificado);
        if ($a1->isExpired()) {
            throw new InvalidArgumentException('Certificado A1 vencido.');
        }

        $pem = $this->a1Manager->writePemFiles($a1);

        try {
            [$numero, $serie, $ambiente] = $this->reservarNumero($empresa, $request->numeroOverride);

            $emitData = $this->toEmitData($empresa, $request, $numero, $serie, $ambiente);
            $built = $this->xmlBuilder->build($emitData);
            $chave = $built['chave'];

            $signedDom = $this->signer->sign(
                $built['dom'],
                $this->a1Manager->privateKeyPem($a1),
                $this->a1Manager->x509CertificateBase64($a1),
            );
            $signedXml = $signedDom->saveXML() ?: '';

            $profile = $request->endpointProfile ?? $this->endpoints->profile();
            $qr = $this->qr()->build(
                chave: $chave,
                tpAmb: $ambiente,
                cscId: (string) $empresa->nfce_csc_id,
                cscToken: (string) $empresa->nfce_csc_token,
                baseUrl: $this->endpoints->qrcode($profile),
            );

            $signedXml = $this->qr()->attachInfNFeSupl(
                $signedXml,
                $qr['url'],
                $this->endpoints->consultaChave($profile),
            );

            if ($nfce) {
                $nfce->update([
                    'chave' => $chave,
                    'numero' => $numero,
                    'serie' => $serie,
                    'ambiente' => $ambiente,
                    'xml_enviado' => $signedXml,
                    'qr_code_url' => $qr['url'],
                    'status' => 'processando',
                    'valor_total' => collect($request->itens)->sum(fn ($i) => $i->valorTotal()),
                    'destinatario_doc' => $request->destDoc,
                    'destinatario_nome' => $request->destNome,
                ]);
            }

            $retorno = $this->soap()->autorizar(
                signedNFeXml: $signedXml,
                certPemPath: $pem['cert'],
                keyPemPath: $pem['key'],
                tpAmb: $ambiente,
                cUF: (string) config('nfce.cUF', '13'),
                profile: $profile,
            );

            if ($nfce) {
                $nfce->update([
                    'status' => 'autorizada',
                    'protocolo' => $retorno['protocolo'],
                    'c_stat' => $retorno['cStat'],
                    'x_motivo' => $retorno['xMotivo'],
                    'xml_autorizado' => $retorno['nfeProc'],
                ]);
            }

            return new NfceEmitResult(
                sucesso: true,
                chave: $chave,
                numero: $numero,
                serie: $serie,
                protocolo: $retorno['protocolo'],
                cStat: $retorno['cStat'],
                xMotivo: $retorno['xMotivo'],
                xmlEnviado: $signedXml,
                xmlAutorizado: $retorno['nfeProc'],
                qrCodeUrl: $qr['url'],
            );
        } catch (SefazRejectionException $e) {
            if ($nfce) {
                $nfce->update([
                    'status' => 'rejeitado',
                    'c_stat' => $e->cStat,
                    'x_motivo' => $e->xMotivo,
                ]);
            }
            throw $e;
        } finally {
            $this->a1Manager->cleanup();
        }
    }

    public function statusServico(Empresa $empresa, ?string $endpointProfile = null): array
    {
        $this->assertEmpresaPronta($empresa, requireCsc: false);

        $certificado = $empresa->certificado;
        if (! $certificado) {
            throw new InvalidArgumentException('Empresa sem certificado A1 ativo.');
        }

        $a1 = $this->a1Manager->loadFromModel($certificado);
        $pem = $this->a1Manager->writePemFiles($a1);

        try {
            return $this->soap()->statusServico(
                certPemPath: $pem['cert'],
                keyPemPath: $pem['key'],
                tpAmb: (int) ($empresa->nfce_ambiente ?: 2),
                cUF: (string) config('nfce.cUF', '13'),
                profile: $endpointProfile,
            );
        } finally {
            $this->a1Manager->cleanup();
        }
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public function reservarNumero(Empresa $empresa, ?int $numeroOverride = null): array
    {
        return DB::transaction(function () use ($empresa, $numeroOverride) {
            $locked = Empresa::query()->whereKey($empresa->id)->lockForUpdate()->firstOrFail();
            $serie = (int) ($locked->nfce_serie ?: 1);
            $ambiente = (int) ($locked->nfce_ambiente ?: 2);

            if ($numeroOverride !== null) {
                return [$numeroOverride, $serie, $ambiente];
            }

            $proximo = ((int) $locked->nfce_ultimo_numero) + 1;
            $locked->nfce_ultimo_numero = $proximo;
            $locked->save();

            return [$proximo, $serie, $ambiente];
        });
    }

    private function toEmitData(
        Empresa $empresa,
        NfceEmitRequest $request,
        int $numero,
        int $serie,
        int $ambiente,
    ): NfceEmitData {
        return new NfceEmitData(
            cnpj: (string) $empresa->cnpj,
            razaoSocial: (string) $empresa->razao_social,
            nomeFantasia: (string) ($empresa->nome_fantasia ?: $empresa->razao_social),
            ie: (string) $empresa->inscricao_estadual,
            crt: (int) ($empresa->crt ?: 1),
            logradouro: (string) $empresa->logradouro,
            numero: (string) $empresa->numero,
            bairro: (string) $empresa->bairro,
            municipio: 'MANAUS',
            uf: (string) ($empresa->uf ?: 'AM'),
            cep: (string) $empresa->cep,
            cMun: (string) $empresa->cod_ibge_mun,
            fone: (string) ($empresa->telefone ?? ''),
            serie: $serie,
            numeroNfce: $numero,
            tpAmb: $ambiente,
            destDoc: $request->destDoc,
            destNome: $request->destNome,
            itens: $request->itens,
            pagamentos: $request->pagamentos,
            naturezaOperacao: $request->naturezaOperacao,
        );
    }

    private function assertEmpresaPronta(Empresa $empresa, bool $requireCsc = true): void
    {
        if (strtoupper((string) $empresa->uf) !== 'AM') {
            throw new InvalidArgumentException('MVP NFC-e restrito a UF=AM.');
        }
        if (empty($empresa->inscricao_estadual)) {
            throw new InvalidArgumentException('Inscrição Estadual (AM) obrigatória.');
        }
        if ($requireCsc && (empty($empresa->nfce_csc_id) || empty($empresa->nfce_csc_token))) {
            throw new InvalidArgumentException('CSC id/token NFC-e obrigatórios.');
        }
        if (empty($empresa->cod_ibge_mun) || empty($empresa->cnpj)) {
            throw new InvalidArgumentException('CNPJ e município IBGE obrigatórios.');
        }
        if (! $empresa->relationLoaded('certificado')) {
            $empresa->load('certificado');
        }
    }
}
