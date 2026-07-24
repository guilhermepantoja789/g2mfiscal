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
use App\Core\FiscalEngine\Xml\EventoCancelamentoBuilder;
use App\Core\FiscalEngine\Xml\InutilizacaoXmlBuilder;
use App\Core\FiscalEngine\Xml\NfceXmlBuilder;
use App\Models\Empresa;
use App\Models\Nfce;
use App\Models\NfceInutilizacao;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RawNativeNfceIssuer implements FiscalIssuerInterface
{
    /** @var array<string, string> */
    private const MUNICIPIOS_AM = [
        '1302603' => 'MANAUS',
        '1301902' => 'ITACOATIARA',
        '1302504' => 'MANACAPURU',
        '1303403' => 'PARINTINS',
        '1301208' => 'COARI',
        '1303536' => 'PRESIDENTE FIGUEIREDO',
        '1304062' => 'TABATINGA',
        '1301700' => 'HUMAITA',
        '1301403' => 'EIRUNEPE',
        '1302900' => 'MAUES',
    ];

    public function __construct(
        private readonly A1Manager $a1Manager = new A1Manager,
        private readonly NfceXmlBuilder $xmlBuilder = new NfceXmlBuilder,
        private readonly XmlDsigSigner $signer = new XmlDsigSigner,
        private readonly ?QrCodeGenerator $qrCodeGenerator = null,
        private readonly SefazEndpoints $endpoints = new SefazEndpoints,
        private readonly ?SefazSoapClient $soapClient = null,
        private readonly EventoCancelamentoBuilder $cancelBuilder = new EventoCancelamentoBuilder,
        private readonly InutilizacaoXmlBuilder $inutBuilder = new InutilizacaoXmlBuilder,
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
            (bool) config('nfce.ssl_verify', false),
            config('nfce.ssl_cafile') ? (string) config('nfce.ssl_cafile') : null,
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
            // Idempotência / contingência: retransmite XML já assinado.
            if ($nfce && filled($nfce->xml_enviado) && filled($nfce->chave)) {
                return $this->retransmitOrRecover($empresa, $nfce, $pem, $request->endpointProfile);
            }

            [$numero, $serie, $ambiente] = $this->reservarNumero($empresa, $request->numeroOverride);
            $profile = $this->endpoints->profileForAmbiente($ambiente, $request->endpointProfile);

            $tpEmis = $request->tpEmis;
            if ($tpEmis !== 9 && $empresa->nfce_contingencia) {
                $tpEmis = 9;
            }

            $xJust = $request->xJustContingencia
                ?: ($empresa->nfce_contingencia_motivo ?: 'Falha de comunicacao com a SEFAZ');

            $emitData = $this->toEmitData($empresa, $request, $numero, $serie, $ambiente, $tpEmis, $xJust);
            $built = $this->xmlBuilder->build($emitData);
            $chave = $built['chave'];

            $signedDom = $this->signer->sign(
                $built['dom'],
                $this->a1Manager->privateKeyPem($a1),
                $this->a1Manager->x509CertificateBase64($a1),
            );
            $signedXml = $signedDom->saveXML() ?: '';

            $valorTotal = collect($request->itens)->sum(fn ($i) => $i->valorTotal());
            $dhEmi = $emitData->dhEmi ?? new \DateTimeImmutable('now', new \DateTimeZone('America/Manaus'));

            if ($tpEmis === 9) {
                $digVal = $this->signer->extractDigestValue($signedXml);
                $qr = $this->qr()->build(
                    chave: $chave,
                    tpAmb: $ambiente,
                    cscId: (string) $empresa->nfce_csc_id,
                    cscToken: (string) $empresa->nfce_csc_token,
                    baseUrl: $this->endpoints->qrcode($profile),
                    tpEmis: 9,
                    dhEmi: $dhEmi->format('Y-m-d\TH:i:sP'),
                    vNF: number_format($valorTotal, 2, '.', ''),
                    digVal: $digVal,
                );
            } else {
                $qr = $this->qr()->build(
                    chave: $chave,
                    tpAmb: $ambiente,
                    cscId: (string) $empresa->nfce_csc_id,
                    cscToken: (string) $empresa->nfce_csc_token,
                    baseUrl: $this->endpoints->qrcode($profile),
                );
            }

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
                    'tp_emis' => $tpEmis,
                    'xml_enviado' => $signedXml,
                    'qr_code_url' => $qr['url'],
                    'status' => $tpEmis === 9 ? 'pendente_transmissao' : 'processando',
                    'valor_total' => $valorTotal,
                    'destinatario_doc' => $request->destDoc,
                    'destinatario_nome' => $request->destNome,
                    'x_motivo' => $tpEmis === 9 ? 'Emitida em contingência offline — aguardando transmissão' : null,
                ]);
            }

            // Contingência: gera/assina/imprime sem SEFAZ; transmissão posterior.
            if ($tpEmis === 9) {
                return new NfceEmitResult(
                    sucesso: true,
                    chave: $chave,
                    numero: $numero,
                    serie: $serie,
                    protocolo: null,
                    cStat: 'OFF',
                    xMotivo: 'Emitida em contingência offline (tpEmis=9)',
                    xmlEnviado: $signedXml,
                    xmlAutorizado: null,
                    qrCodeUrl: $qr['url'],
                );
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
            if ($nfce && $e->cStat === '539') {
                $recovered = $this->recoverDuplicidade539($empresa, $nfce, $pem, $request->endpointProfile, $e);
                if ($recovered !== null) {
                    return $recovered;
                }
            }

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

    public function cancelar(Nfce $nfce, string $motivo, ?string $endpointProfile = null): NfceCancelResult
    {
        $nfce->loadMissing('empresa.certificado');
        $empresa = $nfce->empresa;

        if (! $nfce->podeCancelar()) {
            throw new InvalidArgumentException('NFC-e não está autorizada ou sem protocolo para cancelamento.');
        }

        $prazoMin = (int) config('nfce.cancelamento_prazo_minutos', 30);
        if ($prazoMin > 0 && $nfce->updated_at) {
            $limite = $nfce->updated_at->copy()->addMinutes($prazoMin);
            if (now()->greaterThan($limite)) {
                throw new InvalidArgumentException(
                    "Prazo de cancelamento expirado ({$prazoMin} min após autorização)."
                );
            }
        }

        if (mb_strlen(trim($motivo)) < 15) {
            throw new InvalidArgumentException('Motivo do cancelamento deve ter ao menos 15 caracteres.');
        }

        $this->assertEmpresaPronta($empresa, requireCsc: false);

        $certificado = $empresa->certificado;
        if (! $certificado) {
            throw new InvalidArgumentException('Empresa sem certificado A1 ativo.');
        }

        $a1 = $this->a1Manager->loadFromModel($certificado);
        $pem = $this->a1Manager->writePemFiles($a1);
        $ambiente = (int) ($nfce->ambiente ?: $empresa->nfce_ambiente ?: 2);
        $profile = $this->endpoints->profileForAmbiente($ambiente, $endpointProfile);

        try {
            $built = $this->cancelBuilder->build(
                chave: (string) $nfce->chave,
                cnpj: (string) $empresa->cnpj,
                nProt: (string) $nfce->protocolo,
                xJust: $motivo,
                tpAmb: $ambiente,
            );

            $signedDom = $this->signer->signByLocalName(
                $built['dom'],
                'infEvento',
                $this->a1Manager->privateKeyPem($a1),
                $this->a1Manager->x509CertificateBase64($a1),
            );
            $signedXml = $signedDom->saveXML() ?: '';

            $retorno = $this->soap()->enviarEvento(
                signedEventoXml: $signedXml,
                certPemPath: $pem['cert'],
                keyPemPath: $pem['key'],
                cUF: (string) config('nfce.cUF', '13'),
                profile: $profile,
            );

            $nfce->update([
                'status' => 'cancelada',
                'c_stat' => $retorno['cStat'],
                'x_motivo' => $retorno['xMotivo'],
                'cancelado_em' => now(),
                'protocolo_cancelamento' => $retorno['protocolo'],
                'motivo_cancelamento' => $motivo,
                'xml_evento_cancelamento' => $retorno['xml_retorno'],
            ]);

            return new NfceCancelResult(
                sucesso: true,
                cStat: $retorno['cStat'],
                xMotivo: $retorno['xMotivo'],
                protocolo: $retorno['protocolo'],
                xmlRetorno: $retorno['xml_retorno'],
            );
        } catch (SefazRejectionException $e) {
            $nfce->update([
                'c_stat' => $e->cStat,
                'x_motivo' => $e->xMotivo,
            ]);
            throw $e;
        } finally {
            $this->a1Manager->cleanup();
        }
    }

    public function inutilizar(
        Empresa $empresa,
        int $serie,
        int $numeroIni,
        int $numeroFin,
        string $xJust,
        ?string $endpointProfile = null,
    ): NfceInutilizacaoResult {
        $this->assertEmpresaPronta($empresa, requireCsc: false);

        $certificado = $empresa->certificado;
        if (! $certificado) {
            throw new InvalidArgumentException('Empresa sem certificado A1 ativo.');
        }

        $a1 = $this->a1Manager->loadFromModel($certificado);
        $pem = $this->a1Manager->writePemFiles($a1);
        $ambiente = (int) ($empresa->nfce_ambiente ?: 2);
        $profile = $this->endpoints->profileForAmbiente($ambiente, $endpointProfile);

        $registro = NfceInutilizacao::create([
            'empresa_id' => $empresa->id,
            'serie' => $serie,
            'numero_ini' => $numeroIni,
            'numero_fin' => $numeroFin,
            'ano' => (int) now('America/Manaus')->format('y'),
            'ambiente' => $ambiente,
            'x_just' => $xJust,
        ]);

        try {
            $built = $this->inutBuilder->build(
                cnpj: (string) $empresa->cnpj,
                serie: $serie,
                nNFIni: $numeroIni,
                nNFFin: $numeroFin,
                xJust: $xJust,
                tpAmb: $ambiente,
            );

            $signedDom = $this->signer->signByLocalName(
                $built['dom'],
                'infInut',
                $this->a1Manager->privateKeyPem($a1),
                $this->a1Manager->x509CertificateBase64($a1),
            );
            $signedXml = $signedDom->saveXML() ?: '';

            $retorno = $this->soap()->inutilizar(
                signedInutXml: $signedXml,
                certPemPath: $pem['cert'],
                keyPemPath: $pem['key'],
                cUF: (string) config('nfce.cUF', '13'),
                profile: $profile,
            );

            $registro->update([
                'protocolo' => $retorno['protocolo'],
                'c_stat' => $retorno['cStat'],
                'x_motivo' => $retorno['xMotivo'],
                'xml_enviado' => $signedXml,
                'xml_retorno' => $retorno['xml_retorno'],
            ]);

            return new NfceInutilizacaoResult(
                sucesso: true,
                cStat: $retorno['cStat'],
                xMotivo: $retorno['xMotivo'],
                protocolo: $retorno['protocolo'],
                xmlRetorno: $retorno['xml_retorno'],
            );
        } catch (SefazRejectionException $e) {
            $registro->update([
                'c_stat' => $e->cStat,
                'x_motivo' => $e->xMotivo,
            ]);
            throw $e;
        } finally {
            $this->a1Manager->cleanup();
        }
    }

    /**
     * @param  array{cert: string, key: string}  $pem
     */
    private function retransmitOrRecover(
        Empresa $empresa,
        Nfce $nfce,
        array $pem,
        ?string $requestedProfile,
    ): NfceEmitResult {
        $ambiente = (int) ($nfce->ambiente ?: $empresa->nfce_ambiente ?: 2);
        $profile = $this->endpoints->profileForAmbiente($ambiente, $requestedProfile);
        $signedXml = (string) $nfce->xml_enviado;
        $chave = (string) $nfce->chave;

        try {
            $consulta = $this->soap()->consultar(
                chave: $chave,
                certPemPath: $pem['cert'],
                keyPemPath: $pem['key'],
                tpAmb: $ambiente,
                signedNFeXml: $signedXml,
                profile: $profile,
            );

            if ($consulta['autorizado']) {
                $nfce->update([
                    'status' => 'autorizada',
                    'protocolo' => $consulta['protocolo'],
                    'c_stat' => $consulta['cStat'],
                    'x_motivo' => $consulta['xMotivo'],
                    'xml_autorizado' => $consulta['nfeProc'] ?? $nfce->xml_autorizado,
                ]);

                return new NfceEmitResult(
                    sucesso: true,
                    chave: $chave,
                    numero: (int) $nfce->numero,
                    serie: (int) $nfce->serie,
                    protocolo: $consulta['protocolo'],
                    cStat: $consulta['cStat'],
                    xMotivo: $consulta['xMotivo'],
                    xmlEnviado: $signedXml,
                    xmlAutorizado: $consulta['nfeProc'],
                    qrCodeUrl: $nfce->qr_code_url,
                );
            }
        } catch (\Throwable) {
            // Consulta falhou — reenvia o mesmo XML assinado.
        }

        try {
            $retorno = $this->soap()->autorizar(
                signedNFeXml: $signedXml,
                certPemPath: $pem['cert'],
                keyPemPath: $pem['key'],
                tpAmb: $ambiente,
                cUF: (string) config('nfce.cUF', '13'),
                profile: $profile,
            );
        } catch (SefazRejectionException $e) {
            if ($e->cStat === '539') {
                $recovered = $this->recoverDuplicidade539($empresa, $nfce, $pem, $requestedProfile, $e);
                if ($recovered !== null) {
                    return $recovered;
                }
            }
            throw $e;
        }

        $nfce->update([
            'status' => 'autorizada',
            'protocolo' => $retorno['protocolo'],
            'c_stat' => $retorno['cStat'],
            'x_motivo' => $retorno['xMotivo'],
            'xml_autorizado' => $retorno['nfeProc'],
        ]);

        return new NfceEmitResult(
            sucesso: true,
            chave: $chave,
            numero: (int) $nfce->numero,
            serie: (int) $nfce->serie,
            protocolo: $retorno['protocolo'],
            cStat: $retorno['cStat'],
            xMotivo: $retorno['xMotivo'],
            xmlEnviado: $signedXml,
            xmlAutorizado: $retorno['nfeProc'],
            qrCodeUrl: $nfce->qr_code_url,
        );
    }

    /**
     * cStat 539: número já autorizado na SEFAZ com outra chave (órfão pós-rollback).
     * Adota a chave informada em xMotivo quando a consulta confirma autorização.
     *
     * @param  array{cert: string, key: string}  $pem
     */
    private function recoverDuplicidade539(
        Empresa $empresa,
        Nfce $nfce,
        array $pem,
        ?string $requestedProfile,
        SefazRejectionException $e,
    ): ?NfceEmitResult {
        if (! preg_match('/chNFe:\s*(\d{44})/i', $e->xMotivo, $m)) {
            return null;
        }

        $chaveSefaz = $m[1];
        $ambiente = (int) ($nfce->ambiente ?: $empresa->nfce_ambiente ?: 2);
        $profile = $this->endpoints->profileForAmbiente($ambiente, $requestedProfile);

        try {
            $consulta = $this->soap()->consultar(
                chave: $chaveSefaz,
                certPemPath: $pem['cert'],
                keyPemPath: $pem['key'],
                tpAmb: $ambiente,
                signedNFeXml: '',
                profile: $profile,
            );
        } catch (\Throwable) {
            return null;
        }

        if (! $consulta['autorizado']) {
            return null;
        }

        $nfce->update([
            'chave' => $chaveSefaz,
            'status' => 'autorizada',
            'protocolo' => $consulta['protocolo'],
            'c_stat' => $consulta['cStat'],
            'x_motivo' => 'Recuperada de duplicidade 539: '.$consulta['xMotivo'],
            'xml_autorizado' => $consulta['nfeProc'] ?? $nfce->xml_autorizado,
        ]);

        return new NfceEmitResult(
            sucesso: true,
            chave: $chaveSefaz,
            numero: (int) $nfce->numero,
            serie: (int) $nfce->serie,
            protocolo: $consulta['protocolo'],
            cStat: $consulta['cStat'],
            xMotivo: (string) $nfce->fresh()->x_motivo,
            xmlEnviado: (string) $nfce->xml_enviado,
            xmlAutorizado: $consulta['nfeProc'],
            qrCodeUrl: $nfce->qr_code_url,
        );
    }

    /**
     * Recupera NFC-e rejeitada com cStat 539 adotando a chave já autorizada na SEFAZ.
     */
    public function recuperarDuplicidade(Nfce $nfce, ?string $endpointProfile = null): NfceEmitResult
    {
        $nfce->loadMissing('empresa.certificado');
        $empresa = $nfce->empresa;
        if (! $empresa) {
            throw new InvalidArgumentException('NFC-e sem empresa.');
        }

        if ($nfce->c_stat !== '539' && ! str_contains((string) $nfce->x_motivo, 'Duplicidade')) {
            throw new InvalidArgumentException('NFC-e não está em duplicidade 539.');
        }

        $this->assertEmpresaPronta($empresa, requireCsc: false);
        $certificado = $empresa->certificado;
        if (! $certificado) {
            throw new InvalidArgumentException('Empresa sem certificado A1 ativo.');
        }

        $a1 = $this->a1Manager->loadFromModel($certificado);
        $pem = $this->a1Manager->writePemFiles($a1);

        try {
            $fake = new SefazRejectionException(
                (string) ($nfce->c_stat ?: '539'),
                (string) $nfce->x_motivo,
            );
            $recovered = $this->recoverDuplicidade539($empresa, $nfce, $pem, $endpointProfile, $fake);
            if ($recovered === null) {
                throw new SefazRejectionException(
                    (string) ($nfce->c_stat ?: '539'),
                    'Não foi possível recuperar a chave da duplicidade na SEFAZ.',
                );
            }

            return $recovered;
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
        $tpAmb = (int) ($empresa->nfce_ambiente ?: 2);
        $profile = $this->endpoints->profileForAmbiente($tpAmb, $endpointProfile);

        try {
            return $this->soap()->statusServico(
                certPemPath: $pem['cert'],
                keyPemPath: $pem['key'],
                tpAmb: $tpAmb,
                cUF: (string) config('nfce.cUF', '13'),
                profile: $profile,
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
        int $tpEmis = 1,
        ?string $xJust = null,
    ): NfceEmitData {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('America/Manaus'));

        return new NfceEmitData(
            cnpj: (string) $empresa->cnpj,
            razaoSocial: (string) $empresa->razao_social,
            nomeFantasia: (string) ($empresa->nome_fantasia ?: $empresa->razao_social),
            ie: (string) $empresa->inscricao_estadual,
            crt: (int) ($empresa->crt ?: 1),
            logradouro: (string) $empresa->logradouro,
            numero: (string) $empresa->numero,
            bairro: (string) $empresa->bairro,
            municipio: $this->resolveMunicipio((string) $empresa->cod_ibge_mun),
            uf: (string) ($empresa->uf ?: 'AM'),
            cep: (string) $empresa->cep,
            cMun: (string) $empresa->cod_ibge_mun,
            fone: (string) ($empresa->telefone ?? ''),
            serie: $serie,
            numeroNfce: $numero,
            tpAmb: $ambiente,
            tpEmis: $tpEmis,
            destDoc: $request->destDoc,
            destNome: $request->destNome,
            itens: $request->itens,
            pagamentos: $request->pagamentos,
            naturezaOperacao: $request->naturezaOperacao,
            dhEmi: $now,
            dhCont: $tpEmis === 9 ? $now : null,
            xJust: $tpEmis === 9 ? $xJust : null,
        );
    }

    private function resolveMunicipio(string $cMun): string
    {
        $cMun = preg_replace('/\D/', '', $cMun) ?? '';

        return self::MUNICIPIOS_AM[$cMun] ?? 'MANAUS';
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
