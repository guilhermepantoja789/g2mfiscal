<?php

namespace App\Services\Fiscal;

use App\Models\NotaFiscal;
use App\Services\NfseAmbiente;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTimeInterface;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Carbon;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class NfseDanfseService
{
    public function stream(NotaFiscal $nota): Response
    {
        $pdf = $this->renderPdf($nota);
        $numero = $nota->numero_nfse ?: $nota->id;
        $prefix = $nota->status === 'autorizada' ? 'DANFSe' : 'Espelho-NFSe';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$prefix.'-'.$numero.'.pdf"',
        ]);
    }

    public function renderPdf(NotaFiscal $nota): string
    {
        $pdf = Pdf::loadView('pdf.danfse', $this->viewData($nota))
            ->setPaper('a4', 'portrait');

        $output = $pdf->output();

        if (! is_string($output) || ! str_starts_with($output, '%PDF')) {
            throw new RuntimeException('Não foi possível gerar o PDF da DANFSe.');
        }

        return $output;
    }

    public function renderHtml(NotaFiscal $nota): string
    {
        return view('pdf.danfse', $this->viewData($nota))->render();
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(NotaFiscal $nota): array
    {
        $nota->loadMissing(['empresa', 'cliente', 'servico']);

        $empresa = $nota->empresa;
        if ($empresa === null) {
            throw new RuntimeException('Nota sem empresa para gerar a DANFSe.');
        }

        $xmlContent = $this->decodeXmlAutorizado($nota);
        $chaveAcesso = $this->resolverChaveAcesso($nota, $xmlContent);
        $datas = $this->extrairDatas($nota, $xmlContent);
        $nDps = $this->xmlTag($xmlContent, 'nDPS') ?: $nota->numero_dps;
        $serieXml = $this->xmlTag($xmlContent, 'serie');

        $cidadeEmitente = $this->nomeCidade($empresa->cod_ibge_mun);
        $cliente = $nota->cliente;
        $cidadeTomador = $this->nomeCidade($cliente?->cidade_codigo, $cliente?->cidade_codigo);

        $regimesMap = [
            1 => 'Não Optante',
            2 => 'MEI - Microempreendedor Individual',
            3 => 'Simples Nacional',
        ];
        $regimeTrib = $regimesMap[$empresa->regime_tributario ?? 3] ?? 'Simples Nacional';

        $aliquotaIss = $nota->aliquotaIssEfetiva();
        $valorServico = (float) $nota->valor_servico;
        $valorIss = $nota->calcularValorIss();
        $issRetido = $nota->issRetido();
        $valorLiquido = $nota->calcularValorLiquido();

        $tribIssqnMap = [
            1 => 'Tributável',
            2 => 'Imunidade',
            3 => 'Exportação',
            4 => 'Não Incidência',
        ];

        $finMap = [
            '0' => 'Regular',
            '1' => 'Substituição',
            '2' => 'Complementar',
        ];

        $ambiente = $nota->ambiente ?? 'homologacao';
        $aviso = $this->avisoTipo($nota);
        $urlConsulta = $this->urlConsultaPublica($chaveAcesso);
        $qrBase64 = $urlConsulta ? $this->qrCodeDataUri($urlConsulta) : null;

        $tributos = (object) [
            'v_fed' => (float) ($nota->v_tot_trib_fed ?? 0),
            'v_est' => (float) ($nota->v_tot_trib_est ?? 0),
            'v_mun' => (float) ($nota->v_tot_trib_mun ?? 0),
            'p_fed' => (float) ($nota->p_tot_trib_fed ?? 0),
            'p_est' => (float) ($nota->p_tot_trib_est ?? 0),
            'p_mun' => (float) ($nota->p_tot_trib_mun ?? 0),
        ];
        $tributos->v_total = $tributos->v_fed + $tributos->v_est + $tributos->v_mun;

        $ibscbs = null;
        if (filled($nota->cst_ibscbs) || filled($nota->c_class_trib)) {
            $ibscbs = (object) [
                'cst' => $nota->cst_ibscbs,
                'c_class_trib' => $nota->c_class_trib,
                'c_ind_op' => $nota->c_ind_op,
                'fin_nfse' => $nota->fin_nfse,
                'ind_dest' => $nota->ind_dest,
                'ind_final' => $nota->ind_final,
            ];
        }

        $outras = [];
        if ((int) ($empresa->regime_tributario ?? 3) === 3) {
            $outras[] = 'Documento emitido por ME ou EPP optante pelo Simples Nacional.';
        }
        if ($tributos->v_total > 0) {
            $outras[] = 'Totais aproximados de tributos conforme Lei nº 12.741/2012.';
        }

        return [
            'aviso' => $aviso,
            'emitente' => (object) [
                'razao_social' => $empresa->razao_social,
                'nome_fantasia' => $empresa->nome_fantasia,
                'cnpj' => $empresa->cnpj,
                'inscricao_municipal' => $empresa->inscricao_municipal,
                'endereco' => $empresa->logradouro,
                'numero' => $empresa->numero,
                'complemento' => $empresa->complemento,
                'bairro' => $empresa->bairro,
                'cidade' => $cidadeEmitente,
                'uf' => $empresa->uf,
                'cep' => $empresa->cep,
                'cod_ibge' => $empresa->cod_ibge_mun,
                'telefone' => $empresa->telefone,
                'email' => $empresa->email,
                'regime_tributario' => $regimeTrib,
            ],
            'tomador' => (object) [
                'razao_social' => $cliente?->razao_social ?? $nota->tomador_nome,
                'documento' => $cliente?->cnpj ?? $nota->tomador_cnpj,
                'inscricao_municipal' => $cliente?->inscricao_municipal ?? '',
                'endereco' => $cliente?->logradouro ?? '',
                'numero' => $cliente?->numero ?? '',
                'complemento' => $cliente?->complemento ?? '',
                'bairro' => $cliente?->bairro ?? '',
                'cidade' => $cidadeTomador,
                'uf' => $cliente?->uf ?? '',
                'cep' => $cliente?->cep ?? '',
                'email' => $cliente?->email ?? ($nota->tomador_email ?? ''),
                'telefone' => $cliente?->telefone ?? '',
            ],
            'nota' => (object) [
                'id' => $nota->id,
                'numero' => $nota->numero_nfse,
                'numero_dps' => $nDps,
                'serie' => $serieXml ?: NfseAmbiente::serie(),
                'chave' => $chaveAcesso ?: 'PENDENTE',
                'chave_formatada' => $chaveAcesso ? $this->formatarChave($chaveAcesso) : null,
                'data_emissao' => $datas['emissao'],
                'data_dps' => $datas['dps'],
                'codigo_verificacao' => $nota->codigo_verificacao,
                'competencia' => $datas['competencia'],
                'local_prestacao' => $cidadeEmitente.'/'.($empresa->uf ?? 'AM'),
                'status' => $nota->status,
                'situacao' => $nota->status_label,
                'finalidade' => $finMap[(string) ($nota->fin_nfse ?? '0')] ?? 'Regular',
                'ambiente' => $ambiente,
            ],
            'servico' => (object) [
                'nome' => $nota->servico?->nome ?? '',
                'discriminacao' => $nota->descricao,
                'codigo_nbs' => $nota->servico?->codigo_nbs ?? '',
                'item_lista_servico' => $nota->servico?->codigo_tributacao_municipal ?? '',
                'valor_servico' => $valorServico,
                'valor_deducoes' => 0.00,
                'iss_retido' => $issRetido,
                'valor_iss' => $valorIss,
                'valor_liquido' => $valorLiquido,
                'aliquota_iss' => $aliquotaIss,
                'trib_issqn' => $tribIssqnMap[$nota->trib_issqn ?? 1] ?? 'Tributável',
                'tp_ret_issqn' => $nota->tp_ret_issqn ?? 1,
            ],
            'tributos' => $tributos,
            'ibscbs' => $ibscbs,
            'outras_informacoes' => implode(' ', $outras),
            'xml' => $this->simpleXml($xmlContent),
            'chaveAcesso' => $chaveAcesso,
            'urlConsulta' => $urlConsulta,
            'qrCodeBase64' => $qrBase64,
        ];
    }

    public function avisoTipo(NotaFiscal $nota): ?string
    {
        if ($nota->status !== 'autorizada') {
            return 'espelho';
        }

        if (($nota->ambiente ?? 'homologacao') !== 'producao') {
            return 'homolog';
        }

        return null;
    }

    public function resolverChaveAcesso(NotaFiscal $nota, ?string $xmlContent = null): ?string
    {
        $direta = preg_replace('/\D/', '', (string) $nota->chave_acesso);
        if (strlen($direta) === 50) {
            return $direta;
        }

        $xmlContent ??= $this->decodeXmlAutorizado($nota);
        if ($xmlContent === null || $xmlContent === '') {
            return null;
        }

        if (preg_match('/<chvAcesso>(.*?)<\/chvAcesso>/', $xmlContent, $matches)) {
            $chave = preg_replace('/\D/', '', $matches[1]);
            if (strlen($chave) === 50) {
                return $chave;
            }
        }

        if (preg_match('/Id="NFS([0-9]{50})"/', $xmlContent, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function urlConsultaPublica(?string $chaveAcesso): ?string
    {
        $chave = preg_replace('/\D/', '', (string) $chaveAcesso);
        if (strlen($chave) !== 50) {
            return null;
        }

        $base = rtrim((string) config('services.nfse_nacional.url_consulta', 'https://www.nfse.gov.br/ConsultaPublica'), '/');

        return $base.'/?tpc=1&chave='.$chave;
    }

    public function formatarChave(string $chave): string
    {
        $digits = preg_replace('/\D/', '', $chave) ?? '';

        return trim(implode(' ', str_split($digits, 4)));
    }

    public function decodeXmlAutorizado(NotaFiscal $nota): ?string
    {
        $content = (string) ($nota->xml_autorizado ?? '');
        if ($content === '') {
            return null;
        }

        try {
            if (str_starts_with($content, "\x1f\x8b")) {
                return (string) gzdecode($content);
            }

            if (! str_starts_with(trim($content), '<')) {
                $decoded = base64_decode($content, true);
                if ($decoded && str_starts_with($decoded, "\x1f\x8b")) {
                    return (string) gzdecode($decoded);
                }
                if ($decoded && str_starts_with(trim($decoded), '<')) {
                    return $decoded;
                }
            }
        } catch (Throwable) {
            return $content;
        }

        return $content;
    }

    private function qrCodeDataUri(string $url): ?string
    {
        try {
            $qr = new QrCode(
                data: $url,
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 220,
                margin: 4,
            );

            return (new PngWriter)->write($qr)->getDataUri();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{emissao: Carbon|DateTimeInterface|null, dps: Carbon|DateTimeInterface|null, competencia: Carbon|DateTimeInterface|null}
     */
    private function extrairDatas(NotaFiscal $nota, ?string $xmlContent): array
    {
        $emissao = $nota->emissao ?? $nota->created_at;
        $dps = null;

        $dhEmi = $this->xmlTag($xmlContent, 'dhEmi');
        if ($dhEmi) {
            try {
                $emissao = Carbon::parse($dhEmi);
                $dps = $emissao;
            } catch (Throwable) {
            }
        }

        $dCompet = $this->xmlTag($xmlContent, 'dCompet');
        $competencia = $dCompet ? Carbon::parse($dCompet) : $emissao;

        return [
            'emissao' => $emissao,
            'dps' => $dps ?? $emissao,
            'competencia' => $competencia,
        ];
    }

    private function xmlTag(?string $xml, string $tag): ?string
    {
        if (! $xml || ! preg_match('/<'.$tag.'>(.*?)<\/'.$tag.'>/', $xml, $m)) {
            return null;
        }

        $value = trim(html_entity_decode($m[1]));

        return $value !== '' ? $value : null;
    }

    private function simpleXml(?string $xmlContent): ?SimpleXMLElement
    {
        if (! $xmlContent) {
            return null;
        }

        $loaded = simplexml_load_string(str_replace(['ns1:', 'nfse:'], '', $xmlContent));

        return $loaded instanceof SimpleXMLElement ? $loaded : null;
    }

    private function nomeCidade(?string $codigoIbge, ?string $fallback = 'Manaus'): string
    {
        $codigo = preg_replace('/\D/', '', (string) $codigoIbge);

        return $this->cidadesIbge()[$codigo] ?? ($fallback ?: 'Manaus');
    }

    /**
     * @return array<string, string>
     */
    private function cidadesIbge(): array
    {
        return [
            '1302603' => 'Manaus',
            '1300029' => 'Anamã',
            '1300060' => 'Anori',
            '1300086' => 'Apuí',
            '1300102' => 'Atalaia do Norte',
            '1300144' => 'Autazes',
            '1300201' => 'Barcelos',
            '1300300' => 'Barreirinha',
            '1300409' => 'Benjamin Constant',
            '1300508' => 'Beruri',
            '1300607' => 'Boa Vista do Ramos',
            '1300680' => 'Boca do Acre',
            '1300706' => 'Borba',
            '1300805' => 'Caapiranga',
            '1300839' => 'Canutama',
            '1300904' => 'Carauari',
            '1301001' => 'Careiro',
            '1301100' => 'Careiro da Várzea',
            '1301159' => 'Coari',
            '1301209' => 'Codajás',
            '1301308' => 'Eirunepé',
            '1301407' => 'Envira',
            '1301506' => 'Fonte Boa',
            '1301605' => 'Guajará',
            '1301654' => 'Humaitá',
            '1301704' => 'Ipixuna',
            '1301803' => 'Iranduba',
            '1301852' => 'Itacoatiara',
            '1301902' => 'Itamarati',
            '1302009' => 'Itapiranga',
            '1302108' => 'Japurá',
            '1302207' => 'Juruá',
            '1302306' => 'Jutaí',
            '1302405' => 'Lábrea',
            '1302504' => 'Manacapuru',
            '1302553' => 'Manaquiri',
            '1302702' => 'Manicoré',
            '1302801' => 'Maraã',
            '1302900' => 'Maués',
            '1303007' => 'Nhamundá',
            '1303106' => 'Nova Olinda do Norte',
            '1303205' => 'Novo Airão',
            '1303304' => 'Novo Aripuanã',
            '1303403' => 'Parintins',
            '1303502' => 'Pauini',
            '1303536' => 'Presidente Figueiredo',
            '1303569' => 'Rio Preto da Eva',
            '1303601' => 'Santa Isabel do Rio Negro',
            '1303700' => 'Santo Antônio do Içá',
            '1303809' => 'São Gabriel da Cachoeira',
            '1303908' => 'São Paulo de Olivença',
            '1303957' => 'São Sebastião do Uatumã',
            '1304005' => 'Silves',
            '1304062' => 'Tabatinga',
            '1304104' => 'Tapauá',
            '1304203' => 'Tefé',
            '1304237' => 'Tonantins',
            '1304260' => 'Uarini',
            '1304302' => 'Urucará',
            '1304401' => 'Urucurituba',
        ];
    }
}
