<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use NFePHP\Common\Certificate;
use NFePHP\Common\Signer;

class NfseNacionalService
{
    protected $empresa;
    protected $certificate;
    protected $tempPemPath;

    public function __construct(Empresa $empresa)
    {
        $this->empresa = $empresa;
        $this->carregarCertificado();
    }

    protected function carregarCertificado()
    {
        try {
            if (!Storage::exists($this->empresa->certificado->nome_arquivo)) {
                throw new \Exception("Arquivo do certificado não encontrado.");
            }

            $pfxContent = Storage::get($this->empresa->certificado->nome_arquivo);
            $password = $this->empresa->certificado->senha;

            $this->certificate = Certificate::readPfx($pfxContent, $password);

            $certs = [];
            if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
                throw new \Exception("Senha incorreta ou PFX inválido.");
            }

            $pemContent = $certs['cert'] . "\n" . $certs['pkey'];
            $this->tempPemPath = tempnam(sys_get_temp_dir(), 'cert_nac_') . '.pem';
            file_put_contents($this->tempPemPath, $pemContent);

        } catch (\Exception $e) {
            Log::error("NfseNacionalService: " . $e->getMessage());
            throw $e;
        }
    }

    public function __destruct()
    {
        if (file_exists($this->tempPemPath)) @unlink($this->tempPemPath);
    }

    /**
     * Emite a Nota Fiscal (DPS)
     */
    public function emitirNota(array $dadosNota)
    {
        try {
            $xmlAssinado = $this->gerarXmlAssinado($dadosNota);
            $xmlGzip = gzencode(trim($xmlAssinado), 9);
            $xmlBase64 = base64_encode($xmlGzip);

            $url = config('services.nfse_nacional.url_sefin');

            $response = Http::withOptions([
                'cert' => $this->tempPemPath,
                'verify' => false,
                'headers' => ['Content-Type' => 'application/json']
            ])->post($url, ['dpsXmlGZipB64' => $xmlBase64]);

            return $this->processarRetorno($response, $xmlAssinado);

        } catch (\Exception $e) {
            Log::error("NfseNacionalService Falha: " . $e->getMessage());
            return ['sucesso' => false, 'mensagem' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    /**
     * Consulta uma Nota pela Chave de Acesso
     */
    public function consultarNota(string $chaveAcesso)
    {
        try {
            // Ajuste de URL conforme ambiente
            $ambiente = config('app.env') === 'production' ? 'producao' : 'homologacao';
            $urlConsulta = $ambiente === 'producao'
                ? "https://api.nfse.gov.br/nfse/v1/nfse/{$chaveAcesso}"
                : "https://sefin.producaorestrita.nfse.gov.br/SefinNacional/nfse/{$chaveAcesso}";

            $response = Http::withOptions([
                'cert' => $this->tempPemPath,
                'verify' => false,
                'headers' => ['Content-Type' => 'application/json']
            ])->get($urlConsulta);

            return $this->processarRetorno($response, null);

        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => 'Erro na consulta: ' . $e->getMessage()];
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
            Log::error("ERRO API NACIONAL [HTTP $status]: $body");

            return [
                'sucesso' => false,
                'mensagem' => "Erro HTTP $status: " . ($response->json()['message'] ?? $body),
                'detalhes' => $body
            ];
        }

        $body = $response->json();

        // CASO DE SUCESSO: XML Retornado (nfseXmlGZipB64)
        if (isset($body['nfseXmlGZipB64'])) {
            try {
                // 1. Decodifica e Descompacta o XML da Nota
                $xmlNfse = gzdecode(base64_decode($body['nfseXmlGZipB64']));

                // 2. Extrai os dados vitais (Número e Código Verificação)
                $dom = new \DOMDocument();
                $dom->loadXML($xmlNfse);

                // Tenta pegar o número da nota
                $nNfseNode = $dom->getElementsByTagName('nNFSe')->item(0);
                $numeroNota = $nNfseNode ? $nNfseNode->nodeValue : null;

                // Tenta pegar o código de verificação
                $cVerifNode = $dom->getElementsByTagName('cVerif')->item(0);
                $codVerif = $cVerifNode ? $cVerifNode->nodeValue : null;

                // Tenta pegar o link do PDF se disponível (geralmente não vem no XML, mas montamos depois)
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
                Log::error("Erro ao ler XML retornado: " . $e->getMessage());
                return [
                    'sucesso' => true, // Foi sucesso na API, só falhou nosso parse
                    'mensagem' => 'Nota emitida, mas erro ao ler XML de retorno.',
                    'xml_autorizado' => $body['nfseXmlGZipB64']
                ];
            }
        }

        // Caso de Erro de Negócio
        if (isset($body['erros'])) {
            $listaErros = array_map(fn($e) => "[{$e['codigo']}] {$e['mensagem']}", $body['erros']);
            Log::warning("Nota Rejeitada: " . implode(" | ", $listaErros));

            return [
                'sucesso' => false,
                'mensagem' => 'Rejeição Sefin',
                'erros' => $listaErros,
                'xml_dps_enviado' => $xmlEnviado
            ];
        }

        Log::warning("Resposta desconhecida: " . json_encode($body));
        return ['sucesso' => false, 'mensagem' => 'Resposta desconhecida', 'body_bruto' => $body];
    }

    protected function gerarXmlAssinado(array $dados)
    {
        $codMun = $this->empresa->cod_ibge_mun;
        $cnpjEmitente = str_pad(preg_replace('/[^0-9]/', '', $this->empresa->cnpj), 14, '0', STR_PAD_LEFT);
        $imEmitente   = preg_replace('/[^0-9]/', '', $this->empresa->inscricao_municipal);
        $opSimpNac = $this->empresa->regime_tributario;

        $tagRegApTribSN = '';
        if ($opSimpNac == '3' || $opSimpNac == 'Simples Nacional') {
            $opSimpNac = '3';
            $regApTribSN = $this->empresa->regime_apuracao_sn;
            $tagRegApTribSN = "<regApTribSN>{$regApTribSN}</regApTribSN>";
        }

        $tagRegEspTrib = "<regEspTrib>0</regEspTrib>";

        $docTomador = preg_replace('/[^0-9]/', '', $dados['tomador_doc']);
        if (strlen($docTomador) == 11) {
            $tagTomador = "<CPF>{$docTomador}</CPF>";
        } else {
            $docTomador = str_pad($docTomador, 14, '0', STR_PAD_LEFT);
            $tagTomador = "<CNPJ>{$docTomador}</CNPJ>";
        }

        $tagEnderTomador = '';
        if (!empty($dados['tomador_cep']) && !empty($dados['tomador_cidade_codigo'])) {
            $end_cep    = preg_replace('/[^0-9]/', '', $dados['tomador_cep']);
            $end_lgr    = substr($dados['tomador_endereco'], 0, 255);
            $end_nro    = substr($dados['tomador_numero'] ?? 'S/N', 0, 60);
            $end_bairro = substr($dados['tomador_bairro'] ?? 'Centro', 0, 60); // Fallback para bairro
            $end_cmun   = preg_replace('/[^0-9]/', '', $dados['tomador_cidade_codigo']);
            $tagCpl     = !empty($dados['tomador_complemento']) ? "<xCpl>".substr($dados['tomador_complemento'], 0, 156)."</xCpl>" : "";

            // XML do Endereço (Corrigido: xBairro incluído)
            $tagEnderTomador = "<end>
                <endNac>
                    <cMun>{$end_cmun}</cMun>
                    <CEP>{$end_cep}</CEP>
                </endNac>
                <xLgr>{$end_lgr}</xLgr>
                <nro>{$end_nro}</nro>
                {$tagCpl}
                <xBairro>{$end_bairro}</xBairro>
            </end>";
        }

        $serieFormatada = str_pad($dados['serie'], 5, '0', STR_PAD_LEFT);
        $nDPS_ID = str_pad($dados['numero'], 15, '0', STR_PAD_LEFT);
        $nDPS_XML = (int)$dados['numero'];
        $dataEmissao = date('Y-m-d\TH:i:sP');
        $competencia = $dados['competencia'];
        $tpAmb = config('app.env') === 'production' ? '1' : '2';

        $tribISSQN = $dados['tributacao_iss'] ?? '1';
        $tpRetISSQN = $dados['retencao_iss'] ?? '1';

        // --- TAG pAliq (Alíquota ISS) ---
        // Obrigatória se houver retenção (2 ou 3)
        $tagPAliq = '';
        if ($tpRetISSQN == 2 || $tpRetISSQN == 3) {
            // Usa 'aliquota' que vem do controller (que é o p_tot_trib_mun)
            $aliqVal = number_format($dados['aliquota'] ?? 2.00, 2, '.', '');
            $tagPAliq = "<pAliq>{$aliqVal}</pAliq>";
        }

        // Transparência
        $vTotTribFed = number_format($dados['v_tot_trib_fed'] ?? 0, 2, '.', '');
        $vTotTribEst = number_format($dados['v_tot_trib_est'] ?? 0, 2, '.', '');
        $vTotTribMun = number_format($dados['v_tot_trib_mun'] ?? 0, 2, '.', '');

        $valorServico = number_format($dados['valor'], 2, '.', '');
        $cTribNac = preg_replace('/[^0-9]/', '', $dados['servico_nbs']);
        $cTribMun = preg_replace('/[^0-9]/', '', $dados['servico_municipal']);

        $idDps = "DPS{$codMun}2{$cnpjEmitente}{$serieFormatada}{$nDPS_ID}";

        $xml = <<<XML
<DPS xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.00">
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
            <xNome>{$dados['tomador_nome']}</xNome>
            {$tagEnderTomador}
        </toma>
        <serv>
            <locPrest><cLocPrestacao>{$codMun}</cLocPrestacao></locPrest>
            <cServ>
                <cTribNac>{$cTribNac}</cTribNac>
                <cTribMun>{$cTribMun}</cTribMun>
                <xDescServ>{$dados['discriminacao']}</xDescServ>
            </cServ>
        </serv>
        <valores>
            <vServPrest><vServ>{$valorServico}</vServ></vServPrest>
            <trib>
                <tribMun>
                    <tribISSQN>{$tribISSQN}</tribISSQN>
                    <tpRetISSQN>{$tpRetISSQN}</tpRetISSQN>
                    {$tagPAliq}
                </tribMun>
                <totTrib>
                    <vTotTrib>
                        <vTotTribFed>{$vTotTribFed}</vTotTribFed>
                        <vTotTribEst>{$vTotTribEst}</vTotTribEst>
                        <vTotTribMun>{$vTotTribMun}</vTotTribMun>
                    </vTotTrib>
                </totTrib>
            </trib>
        </valores>
    </infDPS>
</DPS>
XML;

        $xmlAssinado = Signer::sign($this->certificate, $xml, 'infDPS', 'Id', OPENSSL_ALGO_SHA1, [false, false, null, null]);

        if (!str_starts_with($xmlAssinado, '<?xml')) {
            return '<?xml version="1.0" encoding="UTF-8"?>' . $xmlAssinado;
        }

        return $xmlAssinado;
    }

    /**
     * Consulta o CNC usando o Certificado Digital para obter a IM
     */
    public function consultarImViaCnc()
    {
        $this->carregarCertificado();

        if (!$this->tempPemPath || !file_exists($this->tempPemPath)) {
            throw new \Exception("Certificado digital não carregado corretamente.");
        }

        $cnpj = preg_replace('/\D/', '', $this->empresa->cnpj);
        $codMun = preg_replace('/\D/', '', $this->empresa->cod_ibge_mun);

        $baseUrl = config('services.nfse_nacional.url_adn');
        if (empty($baseUrl)) {
            throw new \Exception("A URL do ADN (services.nfse_nacional.url_adn) não está configurada.");
        }

        $url = rtrim($baseUrl, '/') . '/cnc/consulta/cad';

        try {
            $response = Http::withOptions([
                'cert' => $this->tempPemPath,
                'verify' => false,
                'timeout' => 30
            ])->get($url, [
                'codMunicipio' => $codMun,
                'inscricaoFederal' => $cnpj,
            ]);

            if ($response->failed()) {
                $status = $response->status();
                $erroBody = $response->body();
                Log::error("Erro CNC [HTTP $status]: $erroBody");
                throw new \Exception("Falha na consulta CNC: $status - $erroBody");
            }

            $data = $response->json();
            $imEncontrada = null;
            $situacaoEncontrada = '';

            if (!empty($data['ListaCadastroMunicipal'])) {
                foreach ($data['ListaCadastroMunicipal'] as $cadastro) {
                    $situacao = $cadastro['InfCad']['SituacaoEmissaoNFSe'] ?? '';
                    if (in_array(strtoupper($situacao), ['HABILITADO', 'ATIVO'])) {
                        $imEncontrada = $cadastro['InfCad']['InscricaoMunicipal'];
                        $situacaoEncontrada = $situacao;
                        break;
                    }
                }
                if (!$imEncontrada && isset($data['ListaCadastroMunicipal'][0])) {
                    $imEncontrada = $data['ListaCadastroMunicipal'][0]['InfCad']['InscricaoMunicipal'];
                    $situacaoEncontrada = $data['ListaCadastroMunicipal'][0]['InfCad']['SituacaoEmissaoNFSe'] ?? 'DESCONHECIDO';
                }
            }

            if ($imEncontrada) {
                $imLimpa = preg_replace('/[^0-9]/', '', $imEncontrada);
                $imFormatada = str_pad($imLimpa, 15, '0', STR_PAD_LEFT);
                return [
                    'im' => $imFormatada,
                    'situacao' => $situacaoEncontrada
                ];
            }

            throw new \Exception("Nenhuma Inscrição Municipal retornada na lista do CNC.");

        } catch (\Exception $e) {
            Log::error("NfseNacionalService CNC Exception: " . $e->getMessage());
            throw $e;
        } finally {
            if (file_exists($this->tempPemPath)) @unlink($this->tempPemPath);
        }
    }

    /**
     * Baixa o PDF (DANFSe)
     */
    public function downloadDanfse(string $chaveAcesso)
    {
        $this->carregarCertificado();

        $baseUrl = config('services.nfse_nacional.url_adn');
        if (empty($baseUrl)) {
            $baseUrl = "https://adn.producaorestrita.nfse.gov.br";
        }

        $url = rtrim($baseUrl, '/') . "/danfse/{$chaveAcesso}";

        try {
            $response = Http::withOptions([
                'cert' => $this->tempPemPath,
                'verify' => false,
                'timeout' => 60,
            ])->get($url);

            if ($response->failed()) {
                $status = $response->status();
                $erroBody = $response->json()['mensagem'] ?? $response->body();
                Log::error("Erro DANFSe [HTTP $status]: $erroBody");
                throw new \Exception("Falha ao baixar DANFSe: $status - $erroBody");
            }

            return $response->body();

        } catch (\Exception $e) {
            Log::error("NfseNacionalService Download PDF: " . $e->getMessage());
            throw $e;
        }
    }
}
