<?php

namespace App\Services;

use App\Exceptions\CertificadoA1Exception;
use App\Models\Empresa;
use App\Models\NotaFiscal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use NFePHP\Common\Signer;

class NfseNacionalService
{
    protected $empresa;

    protected $certificate;

    protected $tempPemPath = null;

    public function __construct(Empresa $empresa)
    {
        $this->empresa = $empresa;
        $this->carregarCertificado();
    }

    protected function carregarCertificado()
    {
        try {
            if (! $this->empresa->certificado) {
                throw new \Exception('Certificado digital não configurado.');
            }

            $result = app(CertificadoA1Service::class)
                ->loadFromModel($this->empresa->certificado);

            $this->certificate = $result->certificate;

            $this->tempPemPath = tempnam(sys_get_temp_dir(), 'cert_nac_').'.pem';
            file_put_contents($this->tempPemPath, $result->toPem());
            @chmod($this->tempPemPath, 0600);
        } catch (CertificadoA1Exception $e) {
            Log::error('NfseNacionalService: '.$e->getMessage(), [
                'openssl' => $e->opensslError,
            ]);
            throw new \Exception($e->getMessage(), previous: $e);
        } catch (\Exception $e) {
            Log::error('NfseNacionalService: '.$e->getMessage());
            throw $e;
        }
    }

    public function __destruct()
    {
        if ($this->tempPemPath && file_exists($this->tempPemPath)) {
            @unlink($this->tempPemPath);
        }
    }

    /**
     * Emite a Nota Fiscal (DPS).
     * Se $nota já tiver xml_enviado, reenvia o mesmo XML (idempotência pós-timeout).
     */
    public function emitirNota(array $dadosNota, ?NotaFiscal $nota = null)
    {
        try {
            if ($nota && filled($nota->xml_enviado)) {
                $xmlAssinado = (string) $nota->xml_enviado;
            } else {
                $xmlAssinado = $this->gerarXmlAssinado($dadosNota);
                if ($nota) {
                    $nota->update(['xml_enviado' => $xmlAssinado]);
                }
            }

            $xmlGzip = gzencode(trim($xmlAssinado), 9);
            $xmlBase64 = base64_encode($xmlGzip);

            $url = NfseAmbiente::urlSefin();

            $response = Http::withOptions([
                'cert' => $this->tempPemPath,
                'verify' => true,
                'timeout' => 90,
                'headers' => ['Content-Type' => 'application/json'],
            ])->post($url, ['dpsXmlGZipB64' => $xmlBase64]);

            return $this->processarRetorno($response, $xmlAssinado);

        } catch (\InvalidArgumentException $e) {
            Log::error('NfseNacionalService config: '.$e->getMessage());

            return ['sucesso' => false, 'mensagem' => $e->getMessage(), 'erros' => [$e->getMessage()]];
        } catch (\Exception $e) {
            Log::error('NfseNacionalService Falha: '.$e->getMessage());

            return ['sucesso' => false, 'mensagem' => 'Erro interno: '.$e->getMessage()];
        }
    }

    /**
     * Consulta uma Nota pela Chave de Acesso
     */
    public function consultarNota(string $chaveAcesso)
    {
        try {
            $urlConsulta = NfseAmbiente::urlConsulta($chaveAcesso);

            $response = Http::withOptions([
                'cert' => $this->tempPemPath,
                'verify' => true,
                'timeout' => 60,
                'headers' => ['Content-Type' => 'application/json'],
            ])->get($urlConsulta);

            return $this->processarRetorno($response, null);

        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => 'Erro na consulta: '.$e->getMessage()];
        }
    }

    /**
     * Processa o JSON de retorno
     */
    protected function processarRetorno($response, $xmlEnviado = null)
    {
        if ($response->failed()) {
            $status = $response->status();
            $body = $response->body();
            $snippet = mb_substr($body, 0, 500);
            Log::error("ERRO API NACIONAL [HTTP {$status}]", [
                'status' => $status,
                'body_snippet' => $snippet,
            ]);

            $json = $response->json() ?? [];
            $jsonMessage = $json['message'] ?? null;
            $errosApi = $json['erros'] ?? null;

            return [
                'sucesso' => false,
                'mensagem' => "Erro HTTP {$status}: ".($jsonMessage ?? $snippet),
                'detalhes' => $snippet,
                // Propaga erros de negócio (E0116 etc.) para o job não tratar como retry/infra.
                'erros' => is_array($errosApi) && $errosApi !== [] ? $errosApi : null,
                'xml_dps_enviado' => $xmlEnviado,
            ];
        }

        $body = $response->json();

        if (isset($body['nfseXmlGZipB64'])) {
            try {
                $xmlNfse = gzdecode(base64_decode($body['nfseXmlGZipB64']));

                $dom = new \DOMDocument;
                $dom->loadXML($xmlNfse);

                $nNfseNode = $dom->getElementsByTagName('nNFSe')->item(0);
                $numeroNota = $nNfseNode ? $nNfseNode->nodeValue : null;

                $cVerifNode = $dom->getElementsByTagName('cVerif')->item(0);
                $codVerif = $cVerifNode ? $cVerifNode->nodeValue : null;

                return [
                    'sucesso' => true,
                    'mensagem' => 'Nota emitida com sucesso!',
                    'numero_nota' => $numeroNota,
                    'codigo_verificacao' => $codVerif,
                    'chave_acesso' => $body['chaveAcesso'] ?? null,
                    'xml_autorizado' => $body['nfseXmlGZipB64'],
                    'xml_dps_enviado' => $xmlEnviado,
                    'link_pdf' => null,
                ];

            } catch (\Exception $e) {
                Log::error('Erro ao ler XML retornado: '.$e->getMessage());

                return [
                    'sucesso' => true,
                    'mensagem' => 'Nota emitida, mas erro ao ler XML de retorno.',
                    'chave_acesso' => $body['chaveAcesso'] ?? null,
                    'xml_autorizado' => $body['nfseXmlGZipB64'],
                    'xml_dps_enviado' => $xmlEnviado,
                ];
            }
        }

        if (isset($body['erros'])) {
            $listaErros = array_map(fn ($e) => "[{$e['codigo']}] {$e['mensagem']}", $body['erros']);
            Log::warning('Nota Rejeitada: '.implode(' | ', $listaErros));

            return [
                'sucesso' => false,
                'mensagem' => 'Rejeição Sefin',
                'erros' => $listaErros,
                'xml_dps_enviado' => $xmlEnviado,
            ];
        }

        Log::warning('Resposta desconhecida da API NFS-e', [
            'keys' => is_array($body) ? array_keys($body) : [],
        ]);

        return ['sucesso' => false, 'mensagem' => 'Resposta desconhecida', 'body_bruto' => $body];
    }

    protected function sanitize($string)
    {
        if (empty($string)) {
            return '';
        }
        $string = strval($string);

        $string = str_replace(["\r\n", "\r", "\n"], ' - ', $string);
        $string = preg_replace('/[\x00-\x1F\x7F]/', '', $string);

        return htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    protected function gerarXmlAssinado(array $dados)
    {
        $codMun = preg_replace('/\D/', '', $this->empresa->cod_ibge_mun);
        $cnpjEmitente = str_pad(preg_replace('/\D/', '', $this->empresa->cnpj), 14, '0', STR_PAD_LEFT);
        // Produção restrita (homolog): CNC Manaus exige IM com 15 dígitos (E0116).
        // Produção: envia a IM sem zeros à esquerda.
        $imEmitente = preg_replace('/\D/', '', (string) $this->empresa->inscricao_municipal) ?? '';
        if (NfseAmbiente::isProducao()) {
            $imEmitente = ltrim($imEmitente, '0') ?: $imEmitente;
        } else {
            $imEmitente = str_pad($imEmitente, 15, '0', STR_PAD_LEFT);
        }

        $opSimpNac = $this->empresa->regime_tributario;
        $tagRegApTribSN = '';
        if ($opSimpNac == '3' || $opSimpNac == 'Simples Nacional') {
            $opSimpNac = '3';
            $regApTribSN = $this->empresa->regime_apuracao_sn ?: '1';
            $tagRegApTribSN = "<regApTribSN>{$regApTribSN}</regApTribSN>";
        }
        $tagRegEspTrib = '<regEspTrib>0</regEspTrib>';

        $docTomador = preg_replace('/\D/', '', $dados['tomador_doc']);
        if (strlen($docTomador) > 11) {
            $docTomador = str_pad($docTomador, 14, '0', STR_PAD_LEFT);
            $tagTomador = "<CNPJ>{$docTomador}</CNPJ>";
        } else {
            $docTomador = str_pad($docTomador, 11, '0', STR_PAD_LEFT);
            $tagTomador = "<CPF>{$docTomador}</CPF>";
        }

        $nomeTomador = $this->sanitize($dados['tomador_nome']);
        $endLgr = $this->sanitize($dados['tomador_endereco']);
        $endNro = $this->sanitize($dados['tomador_numero'] ?? 'SN');
        $endBairro = $this->sanitize($dados['tomador_bairro'] ?? 'Centro');
        $endCep = preg_replace('/\D/', '', $dados['tomador_cep']);
        $endCmun = preg_replace('/\D/', '', $dados['tomador_cidade_codigo']);
        $tagCpl = ! empty($dados['tomador_complemento'])
            ? '<xCpl>'.$this->sanitize($dados['tomador_complemento']).'</xCpl>'
            : '';

        $tagEnderTomador = "<end>
            <endNac>
                <cMun>{$endCmun}</cMun>
                <CEP>{$endCep}</CEP>
            </endNac>
            <xLgr>{$endLgr}</xLgr>
            <nro>{$endNro}</nro>
            {$tagCpl}
            <xBairro>{$endBairro}</xBairro>
        </end>";

        $tpAmb = (string) NfseAmbiente::tpAmb();
        $serieNum = $dados['serie'] ?? NfseAmbiente::serie();

        $serieFormatada = str_pad((string) $serieNum, 5, '0', STR_PAD_LEFT);
        $nDPS_ID = str_pad($dados['numero'], 15, '0', STR_PAD_LEFT);
        $nDPS_XML = (int) $dados['numero'];
        $dataEmissao = date('Y-m-d\TH:i:sP');
        $competencia = $dados['competencia'];

        $idDps = "DPS{$codMun}2{$cnpjEmitente}{$serieFormatada}{$nDPS_ID}";

        $descServico = $this->sanitize($dados['discriminacao']);

        $cTribNacRaw = preg_replace('/\D/', '', $dados['servico_nbs']);
        $cTribNac = str_pad($cTribNacRaw, 6, '0', STR_PAD_LEFT);

        $cTribMun = preg_replace('/\D/', '', $dados['servico_municipal']);

        $valServ = number_format($dados['valor'], 2, '.', '');
        $tribISS = $dados['tributacao_iss'];
        $retISS = $dados['retencao_iss'];

        $tagPAliq = '';
        if ($retISS == 2 || $retISS == 3) {
            $aliq = number_format($dados['aliquota'] ?? 2.00, 2, '.', '');
            $tagPAliq = "<pAliq>{$aliq}</pAliq>";
        }

        $vFed = number_format($dados['v_tot_trib_fed'] ?? 0, 2, '.', '');
        $vEst = number_format($dados['v_tot_trib_est'] ?? 0, 2, '.', '');
        $vMun = number_format($dados['v_tot_trib_mun'] ?? 0, 2, '.', '');

        $tagIbscbs = NfseIbscbsBuilder::toXml($dados);

        $xml = <<<XML
<DPS xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01">
    <infDPS Id="{$idDps}">
        <tpAmb>{$tpAmb}</tpAmb>
        <dhEmi>{$dataEmissao}</dhEmi>
        <verAplic>1.0.0</verAplic>
        <serie>{$serieFormatada}</serie>
        <nDPS>{$nDPS_XML}</nDPS>
        <dCompet>{$competencia}</dCompet>
        <tpEmit>1</tpEmit>
        <cLocEmi>{$codMun}</cLocEmi>
        <prest>
            <CNPJ>{$cnpjEmitente}</CNPJ>
            <IM>{$imEmitente}</IM>
            <regTrib>
                <opSimpNac>{$opSimpNac}</opSimpNac>
                {$tagRegApTribSN}
                {$tagRegEspTrib}
            </regTrib>
        </prest>
        <toma>
            {$tagTomador}
            <xNome>{$nomeTomador}</xNome>
            {$tagEnderTomador}
        </toma>
        <serv>
            <locPrest><cLocPrestacao>{$codMun}</cLocPrestacao></locPrest>
            <cServ>
                <cTribNac>{$cTribNac}</cTribNac>
                <cTribMun>{$cTribMun}</cTribMun>
                <xDescServ>{$descServico}</xDescServ>
            </cServ>
        </serv>
        <valores>
            <vServPrest><vServ>{$valServ}</vServ></vServPrest>
            <trib>
                <tribMun>
                    <tribISSQN>{$tribISS}</tribISSQN>
                    <tpRetISSQN>{$retISS}</tpRetISSQN>
                    {$tagPAliq}
                </tribMun>
                <totTrib>
                    <vTotTrib>
                        <vTotTribFed>{$vFed}</vTotTribFed>
                        <vTotTribEst>{$vEst}</vTotTribEst>
                        <vTotTribMun>{$vMun}</vTotTribMun>
                    </vTotTrib>
                </totTrib>
            </trib>
        </valores>
        {$tagIbscbs}
    </infDPS>
</DPS>
XML;

        $xmlAssinado = Signer::sign($this->certificate, $xml, 'infDPS', 'Id', OPENSSL_ALGO_SHA256, [false, false, null, null]);
        $xmlAssinado = trim($xmlAssinado);

        if (! str_starts_with($xmlAssinado, '<?xml')) {
            return '<?xml version="1.0" encoding="UTF-8"?>'."\n".$xmlAssinado;
        }

        return $xmlAssinado;
    }

    /**
     * Consulta o CNC usando o Certificado Digital para obter a IM
     */
    public function consultarImViaCnc()
    {
        $this->carregarCertificado();

        if (! $this->tempPemPath || ! file_exists($this->tempPemPath)) {
            throw new \Exception('Certificado digital não carregado corretamente.');
        }

        $cnpj = preg_replace('/\D/', '', $this->empresa->cnpj);
        $codMun = preg_replace('/\D/', '', $this->empresa->cod_ibge_mun);

        $baseUrl = NfseAmbiente::urlAdn();
        $url = $baseUrl.'/cnc/consulta/cad';

        try {
            $response = Http::withOptions([
                'cert' => $this->tempPemPath,
                'verify' => true,
                'timeout' => 30,
            ])->get($url, [
                'codMunicipio' => $codMun,
                'inscricaoFederal' => $cnpj,
            ]);

            if ($response->failed()) {
                $status = $response->status();
                $erroBody = mb_substr($response->body(), 0, 500);
                Log::error("Erro CNC [HTTP {$status}]: {$erroBody}");
                throw new \Exception("Falha na consulta CNC: {$status} - {$erroBody}");
            }

            $data = $response->json();
            $imEncontrada = null;
            $situacaoEncontrada = '';

            if (! empty($data['ListaCadastroMunicipal'])) {
                foreach ($data['ListaCadastroMunicipal'] as $cadastro) {
                    $situacao = $cadastro['InfCad']['SituacaoEmissaoNFSe'] ?? '';
                    if (in_array(strtoupper($situacao), ['HABILITADO', 'ATIVO'])) {
                        $imEncontrada = $cadastro['InfCad']['InscricaoMunicipal'];
                        $situacaoEncontrada = $situacao;
                        break;
                    }
                }
                if (! $imEncontrada && isset($data['ListaCadastroMunicipal'][0])) {
                    $imEncontrada = $data['ListaCadastroMunicipal'][0]['InfCad']['InscricaoMunicipal'];
                    $situacaoEncontrada = $data['ListaCadastroMunicipal'][0]['InfCad']['SituacaoEmissaoNFSe'] ?? 'DESCONHECIDO';
                }
            }

            if ($imEncontrada) {
                $imLimpa = preg_replace('/[^0-9]/', '', $imEncontrada);

                return [
                    'im' => $imLimpa,
                    'situacao' => $situacaoEncontrada,
                ];
            }

            throw new \Exception('Nenhuma Inscrição Municipal retornada na lista do CNC.');

        } catch (\Exception $e) {
            Log::error('NfseNacionalService CNC Exception: '.$e->getMessage());
            throw $e;
        } finally {
            if ($this->tempPemPath && file_exists($this->tempPemPath)) {
                @unlink($this->tempPemPath);
            }
            $this->tempPemPath = null;
        }
    }

    /**
     * Baixa o PDF (DANFSe)
     */
    public function downloadDanfse(string $chaveAcesso)
    {
        $this->carregarCertificado();

        $baseUrl = NfseAmbiente::urlAdn();
        $url = $baseUrl.'/danfse/'.$chaveAcesso;
        $maxAttempts = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::withOptions([
                    'cert' => $this->tempPemPath,
                    'verify' => true,
                    'timeout' => 60,
                ])->get($url);

                if ($response->successful()) {
                    $body = $response->body();
                    if (str_starts_with($body, '%PDF')) {
                        return $body;
                    }
                    throw new \Exception('Resposta da ADN não é um PDF válido.');
                }

                $status = $response->status();
                $erroBody = $response->json()['mensagem'] ?? mb_substr($response->body(), 0, 500);
                Log::error("Erro DANFSe [HTTP {$status}] tentativa {$attempt}/{$maxAttempts}: {$erroBody}");

                // 502/503/504 costumam ser instabilidade da ADN (comum em produção restrita).
                if (in_array($status, [502, 503, 504], true) && $attempt < $maxAttempts) {
                    sleep(2 * $attempt);
                    continue;
                }

                throw new \Exception("Falha ao baixar DANFSe: {$status} - {$erroBody}");
            } catch (\Exception $e) {
                $lastException = $e;
                if ($attempt >= $maxAttempts || ! str_contains($e->getMessage(), 'Falha ao baixar DANFSe: 50')) {
                    Log::error('NfseNacionalService Download PDF: '.$e->getMessage());
                    throw $e;
                }
                sleep(2 * $attempt);
            }
        }

        throw $lastException ?? new \Exception('Falha ao baixar DANFSe.');
    }
}
