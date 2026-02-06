<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Empresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use NFePHP\Common\Certificate;
use NFePHP\Common\Signer;

class TesteEmissaoReal extends Command
{
    protected $signature = 'fiscal:teste-real {empresa_id}';
    protected $description = 'Gera XML Blindado e Envia para API Nacional (Homologação)';

    public function handle()
    {
        $id = $this->argument('empresa_id');
        $empresa = Empresa::find($id);

        if (!$empresa || !$empresa->certificado) {
            $this->error("Empresa ou certificado não encontrados.");
            return;
        }

        $this->info("=== CORREÇÃO SCHEMA XSD (ORDEM DAS TAGS) ===");

        // =================================================================
        // 1. PREPARAÇÃO DO CERTIFICADO
        // =================================================================
        try {
            $pfxContent = Storage::get($empresa->certificado->nome_arquivo);
            $password = $empresa->certificado->senha;
            $certificate = Certificate::readPfx($pfxContent, $password);

            $certs = [];
            if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
                $this->error("Erro PFX.");
                return;
            }
            $pemContent = $certs['cert'] . "\n" . $certs['pkey'];
            $tempPemPath = tempnam(sys_get_temp_dir(), 'cert_nacional_') . '.pem';
            file_put_contents($tempPemPath, $pemContent);
        } catch (\Exception $e) {
            $this->error("Erro Certificado: " . $e->getMessage());
            return;
        }

        // =================================================================
        // 2. DADOS
        // =================================================================
        $serie = '99';
        $numero = rand(50000, 99999);
        $dataEmissao = date('Y-m-d\TH:i:sP');
        $competencia = date('Y-m-d');

        $codMun = preg_replace('/\D/', '', $empresa->cod_ibge_mun);
        $cnpjEmitente = str_pad(preg_replace('/\D/', '', $empresa->cnpj), 14, '0', STR_PAD_LEFT);
        $imEmitente = str_pad(preg_replace('/\D/', '', $empresa->inscricao_municipal), 15, '0', STR_PAD_LEFT);

        $serieFormatada = str_pad($serie, 5, '0', STR_PAD_LEFT);
        $numeroFormatado = str_pad($numero, 15, '0', STR_PAD_LEFT);

        $idDps = "DPS{$codMun}2{$cnpjEmitente}{$serieFormatada}{$numeroFormatado}";

        $this->info("ID DPS: $idDps");

        // DADOS TOMADOR
        $docTomador = '06990590000123';
        $nomeTomador = 'TOMADOR TESTE S.A.';
        $endCep = '69029130'; $endCmun = '1302603';
        $endLgr = 'Rua Teste'; $endNro = '100'; $endBairro = 'Centro';

        $nbs = '010601';
        $desc = $this->sanitize("Teste Emissao API Nacional ID $numero");
        $valor = '1.00';

        // =================================================================
        // 3. XML (CORREÇÃO DA ORDEM DAS TAGS)
        // =================================================================

        // CORREÇÃO CRÍTICA: <endNac> DEVE VIR PRIMEIRO!
        // Ordem Correta: endNac -> xLgr -> nro -> xCpl -> xBairro
        $tagEndereco = "<end><endNac><cMun>{$endCmun}</cMun><CEP>{$endCep}</CEP></endNac><xLgr>{$endLgr}</xLgr><nro>{$endNro}</nro><xBairro>{$endBairro}</xBairro></end>";

        // TRIBUTOS (Mantendo ordem padrão: tribISSQN -> tpRetISSQN -> pAliq)
        $tagTrib = "<trib><tribMun><tribISSQN>1</tribISSQN><tpRetISSQN>1</tpRetISSQN></tribMun><totTrib><vTotTrib><vTotTribFed>0.00</vTotTribFed><vTotTribEst>0.00</vTotTribEst><vTotTribMun>0.00</vTotTribMun></vTotTrib></totTrib></trib>";

        $xml = "<DPS xmlns=\"http://www.sped.fazenda.gov.br/nfse\" versao=\"1.00\"><infDPS Id=\"{$idDps}\"><tpAmb>2</tpAmb><dhEmi>{$dataEmissao}</dhEmi><verAplic>1.0.0</verAplic><serie>{$serieFormatada}</serie><nDPS>{$numero}</nDPS><dCompet>{$competencia}</dCompet><tpEmit>1</tpEmit><cLocEmi>{$codMun}</cLocEmi><prest><CNPJ>{$cnpjEmitente}</CNPJ><IM>{$imEmitente}</IM><regTrib><opSimpNac>3</opSimpNac><regApTribSN>1</regApTribSN><regEspTrib>0</regEspTrib></regTrib></prest><toma><CNPJ>{$docTomador}</CNPJ><xNome>{$nomeTomador}</xNome>{$tagEndereco}</toma><serv><locPrest><cLocPrestacao>{$codMun}</cLocPrestacao></locPrest><cServ><cTribNac>{$nbs}</cTribNac><cTribMun>100</cTribMun><xDescServ>{$desc}</xDescServ></cServ></serv><valores><vServPrest><vServ>{$valor}</vServ></vServPrest>{$tagTrib}</valores></infDPS></DPS>";

        // =================================================================
        // 4. ASSINATURA
        // =================================================================
        try {
            $xmlAssinado = Signer::sign($certificate, $xml, 'infDPS', 'Id', OPENSSL_ALGO_SHA1, [false, false, null, null]);
            if (!str_starts_with($xmlAssinado, '<?xml')) {
                $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlAssinado;
            }
        } catch (\Exception $e) {
            $this->error("Erro Assinatura: " . $e->getMessage());
            return;
        }

        // =================================================================
        // 5. ENVIO
        // =================================================================
        $xmlGzip = gzencode(trim($xmlAssinado), 9);
        $xmlBase64 = base64_encode($xmlGzip);

        $url = 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional/nfse';
        $this->info("Enviando para: $url");

        try {
            $response = Http::withOptions([
                'cert' => $tempPemPath,
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ])->post($url, ['dpsXmlGZipB64' => $xmlBase64]);

            if (file_exists($tempPemPath)) @unlink($tempPemPath);

            $status = $response->status();
            $body = $response->json();

            $this->line("\n================ RESULTADO ($status) ================");

            if ($status != 200 && $status != 201) {
                $this->error("❌ FALHA:");
                $this->line(json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->info("✅ SUCESSO!");
                $this->info("Chave: " . ($body['chaveAcesso'] ?? ''));
                $this->line("XML Retorno: " . ($body['nfseXmlGZipB64'] ?? ''));
            }

        } catch (\Exception $e) {
            $this->error("Exception HTTP: " . $e->getMessage());
        }
    }

    private function sanitize($string)
    {
        if (empty($string)) return '';
        $string = strval($string);
        $string = str_replace(["\r\n", "\r", "\n"], " ", $string);
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        if ($clean === false) $clean = preg_replace('/[^\x20-\x7E]/', '', $string);
        return preg_replace('/[^a-zA-Z0-9\s\-\.\,\/\:\;]/', '', $clean);
    }
}
