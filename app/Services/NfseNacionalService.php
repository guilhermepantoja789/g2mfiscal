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
     * Consulta uma Nota pela Chave de Acesso (NOVO)
     */
    public function consultarNota(string $chaveAcesso)
    {
        try {
            // URL Base da API (remove o final '/emissao' e adiciona o endpoint de consulta)
            // Ex: https://.../nfse/v1/nfse/{chave}
            $baseUrl = str_replace('/emissao', '', config('services.nfse_nacional.url_sefin'));
            // Ajuste manual caso a URL de config não tenha /emissao no final, mas o padrão é ter.
            // Para garantir, vamos montar a URL de consulta padrão:
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
     * Processa o JSON de retorno (Ajustado para ler o XML da NFSe)
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
        // 1. DADOS DA EMPRESA
        $codMun = $this->empresa->cod_ibge_mun;
        $cnpjEmitente = str_pad(preg_replace('/[^0-9]/', '', $this->empresa->cnpj), 14, '0', STR_PAD_LEFT);
        $imEmitente   = preg_replace('/[^0-9]/', '', $this->empresa->inscricao_municipal);
        $opSimpNac = $this->empresa->regime_tributario;

        $tagRegApTribSN = '';
        if ($opSimpNac == '2' || $opSimpNac == '3') {
            $valorAp = $this->empresa->regime_apuracao_sn ?? '1';
            $tagRegApTribSN = "<regApTribSN>{$valorAp}</regApTribSN>";
        }
        $tagRegEspTrib = "<regEspTrib>".($this->empresa->regime_especial_tributacao ?? '0')."</regEspTrib>";

        // 2. DADOS FORMATADOS
        $cTribNac = str_pad(preg_replace('/[^0-9]/', '', $dados['servico_nbs']), 6, '0', STR_PAD_LEFT);
        $cTribMun = str_pad(preg_replace('/[^0-9]/', '', $dados['servico_municipal']), 3, '0', STR_PAD_LEFT);

        $docTomador = preg_replace('/[^0-9]/', '', $dados['tomador_doc']);
        if (strlen($docTomador) == 11) {
            $tagTomador = "<CPF>{$docTomador}</CPF>";
        } else {
            $docTomador = str_pad($docTomador, 14, '0', STR_PAD_LEFT);
            $tagTomador = "<CNPJ>{$docTomador}</CNPJ>";
        }

        $tagEnderTomador = '';

        if (!empty($dados['tomador_endereco'])) {
            $end_cep    = preg_replace('/[^0-9]/', '', $dados['tomador_cep'] ?? '');
            $end_lgr    = substr($dados['tomador_endereco'], 0, 255);
            $end_nro    = substr($dados['tomador_numero'] ?? 'S/N', 0, 60);
            $end_cpl    = substr($dados['tomador_complemento'] ?? '', 0, 156);
            $end_bairro = substr($dados['tomador_bairro'] ?? '', 0, 60);
            $end_cmun   = preg_replace('/[^0-9]/', '', $dados['tomador_cidade_codigo'] ?? '');

            // Tag opcional de complemento
            $tagCpl = !empty($end_cpl) ? "<xCpl>{$end_cpl}</xCpl>" : "";

            if ($end_cep && $end_cmun) {
                // ESTRUTURA CORRETA CONFORME LEIAUTE:
                // 1. endNac (Apenas cMun e CEP)
                // 2. xLgr (Rua)
                // 3. nro (Número)
                // 4. xCpl (Complemento - Opcional)
                // 5. xBairro (Bairro)
                // *OBS: Não enviar a tag <UF>, o cMun já resolve isso.

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
        }

        // CONTATO
        $tagFone = !empty($dados['tomador_telefone']) ? "<fone>" . preg_replace('/[^0-9]/', '', $dados['tomador_telefone']) . "</fone>" : "";
        $tagEmail = !empty($dados['tomador_email']) ? "<email>{$dados['tomador_email']}</email>" : "";
        // 3. IDs
        $serieFormatada = str_pad($dados['serie'], 5, '0', STR_PAD_LEFT);
        $nDPS_ID = str_pad($dados['numero'], 15, '0', STR_PAD_LEFT);
        $nDPS_XML = (int)$dados['numero'];

        $locPrestacao = $dados['codigo_municipio_prestacao'] ?? $codMun;
        $dataEmissao = date('Y-m-d\TH:i:sP');
        $competencia = $dados['competencia'] ?? date('Y-m-d');
        $tpAmb = config('app.env') === 'production' ? '1' : '2';
        $tribISSQN = $dados['tributacao_iss'] ?? '1';
        $tpRetISSQN = $dados['retencao_iss'] ?? '1';

        // 4. XML
        $tpInsc = '2';
        $idDps = "DPS{$codMun}{$tpInsc}{$cnpjEmitente}{$serieFormatada}{$nDPS_ID}";

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
            {$tagFone}
            {$tagEmail}
        </toma>
        <serv>
            <locPrest><cLocPrestacao>{$locPrestacao}</cLocPrestacao></locPrest>
            <cServ>
                <cTribNac>{$cTribNac}</cTribNac>
                <cTribMun>{$cTribMun}</cTribMun>
                <xDescServ>{$dados['discriminacao']}</xDescServ>
            </cServ>
        </serv>
        <valores>
            <vServPrest><vServ>{$dados['valor']}</vServ></vServPrest>
            <trib>
                <tribMun><tribISSQN>{$tribISSQN}</tribISSQN><tpRetISSQN>{$tpRetISSQN}</tpRetISSQN></tribMun>
                <totTrib><vTotTrib><vTotTribFed>0.00</vTotTribFed><vTotTribEst>0.00</vTotTribEst><vTotTribMun>0.00</vTotTribMun></vTotTrib></totTrib>
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
        // 1. Carrega o certificado
        $this->carregarCertificado();

        if (!$this->tempPemPath || !file_exists($this->tempPemPath)) {
            throw new \Exception("Certificado digital não carregado corretamente.");
        }

        // 2. Prepara os dados limpos
        $cnpj = preg_replace('/\D/', '', $this->empresa->cnpj);
        $codMun = preg_replace('/\D/', '', $this->empresa->cod_ibge_mun);

        // 3. Define a URL base via Config
        $baseUrl = config('services.nfse_nacional.url_adn');

        if (empty($baseUrl)) {
            throw new \Exception("A URL do ADN (services.nfse_nacional.url_adn) não está configurada.");
        }

        $url = rtrim($baseUrl, '/') . '/cnc/consulta/cad';

        try {
            // 4. Faz a requisição GET
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

            // 5. Processa o Retorno JSON para achar a IM Ativa/Habilitada
            $imEncontrada = null;
            $situacaoEncontrada = '';

            if (!empty($data['ListaCadastroMunicipal'])) {
                // Prioridade: HABILITADO ou ATIVO
                foreach ($data['ListaCadastroMunicipal'] as $cadastro) {
                    $situacao = $cadastro['InfCad']['SituacaoEmissaoNFSe'] ?? '';

                    if (in_array(strtoupper($situacao), ['HABILITADO', 'ATIVO'])) {
                        $imEncontrada = $cadastro['InfCad']['InscricaoMunicipal'];
                        $situacaoEncontrada = $situacao;
                        break;
                    }
                }

                // Se não achou habilitado, pega o primeiro da lista como fallback
                if (!$imEncontrada && isset($data['ListaCadastroMunicipal'][0])) {
                    $imEncontrada = $data['ListaCadastroMunicipal'][0]['InfCad']['InscricaoMunicipal'];
                    $situacaoEncontrada = $data['ListaCadastroMunicipal'][0]['InfCad']['SituacaoEmissaoNFSe'] ?? 'DESCONHECIDO';
                }
            }

            if ($imEncontrada) {
                // --- NOVA LÓGICA DE FORMATAÇÃO ---
                // 1. Remove qualquer caractere não numérico por segurança
                $imLimpa = preg_replace('/[^0-9]/', '', $imEncontrada);

                // 2. Preenche com zeros à esquerda até ter 15 dígitos
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
     * Baixa o PDF (DANFSe) da nota pela Chave de Acesso
     * Endpoint: GET /danfse/{chaveAcesso}
     * Retorna: String binária do PDF
     */
    public function downloadDanfse(string $chaveAcesso)
    {
        // 1. Carrega certificado para comunicação segura
        $this->carregarCertificado();

        // 2. Monta a URL
        // Ex: https://adn.producaorestrita.nfse.gov.br/danfse/1324...
        $baseUrl = config('services.nfse_nacional.url_adn');

        if (empty($baseUrl)) {
            // Fallback de segurança se a config falhar
            $baseUrl = "https://adn.producaorestrita.nfse.gov.br";
        }

        $url = rtrim($baseUrl, '/') . "/danfse/{$chaveAcesso}";

        try {
            // 3. Faz a requisição GET com o certificado
            $response = Http::withOptions([
                'cert' => $this->tempPemPath,
                'verify' => false, // Ignora validação SSL se necessário em homologação
                'timeout' => 60,   // PDF pode demorar um pouco mais
            ])->get($url);

            // 4. Tratamento de Erro
            if ($response->failed()) {
                $status = $response->status();
                // Tenta ler se veio um JSON de erro ou texto
                $erroBody = $response->json()['mensagem'] ?? $response->body();

                Log::error("Erro DANFSe [HTTP $status]: $erroBody");
                throw new \Exception("Falha ao baixar DANFSe: $status - $erroBody");
            }

            // 5. Sucesso: Retorna o conteúdo binário do PDF
            return $response->body();

        } catch (\Exception $e) {
            Log::error("NfseNacionalService Download PDF: " . $e->getMessage());
            throw $e;
        }
    }

}
