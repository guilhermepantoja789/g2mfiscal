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
    protected $signature = 'teste:emissao_real {empresa_id}';
    protected $description = 'Gera XML, Assina, Compacta e Envia para API Nacional';

    public function handle()
    {
        $id = $this->argument('empresa_id');
        $empresa = Empresa::find($id);

        if (!$empresa || !$empresa->certificado) {
            $this->error("Empresa ou certificado não encontrados.");
            return;
        }

        // =================================================================
        // 1. PREPARAÇÃO DO CERTIFICADO
        // =================================================================
        $this->info("1. Preparando certificado...");

        try {
            $pfxContent = Storage::get($empresa->certificado->nome_arquivo);
            $password = $empresa->certificado->senha;
            $certificate = Certificate::readPfx($pfxContent, $password);

            $certs = [];
            if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
                $this->error("Não foi possível ler o arquivo PFX.");
                return;
            }
            $pemContent = $certs['cert'] . "\n" . $certs['pkey'];
            $tempPemPath = tempnam(sys_get_temp_dir(), 'cert_nacional_') . '.pem';
            file_put_contents($tempPemPath, $pemContent);
        } catch (\Exception $e) {
            $this->error("Erro ao processar certificado: " . $e->getMessage());
            return;
        }

        // =================================================================
        // 2. GERAÇÃO DO XML DA DPS
        // =================================================================
        $this->info("2. Gerando XML da DPS...");

        $serie = '1';
        $numero = rand(1000, 9999);
        $dataEmissao = date('Y-m-d\TH:i:sP'); // Com Timezone
        $dataCompetencia = date('Y-m-d');      // Apenas Data

        // Dados Fixos para Teste
        $codMun = '1302603'; // Manaus
        $tipoInsc = '2';     // CNPJ

        $cnpjLimpo = preg_replace('/[^0-9]/', '', $empresa->cnpj);
        $cnpjFormatado = str_pad($cnpjLimpo, 14, '0', STR_PAD_LEFT);
        $serieFormatada = str_pad($serie, 5, '0', STR_PAD_LEFT);
        $numeroFormatado = str_pad($numero, 15, '0', STR_PAD_LEFT);

        // ID da DPS
        $idDps = "DPS{$codMun}{$tipoInsc}{$cnpjFormatado}{$serieFormatada}{$numeroFormatado}";
        $this->info("ID Gerado: $idDps");

        $imLimpa = preg_replace('/[^0-9]/', '', $empresa->inscricao_municipal);
        $this->info("IM Informada: $imLimpa");
        // Se estiver vazia para teste, tente descobrir a IM correta.
        // Manaus geralmente tem IMs de 6 a 7 dígitos.
        if (empty($imLimpa)) {
            $this->error("ERRO: Inscrição Municipal é obrigatória para este município!");
            return;
        }

        // XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';

        $xml .= <<<XML
<DPS xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.00">
    <infDPS Id="{$idDps}">
        <tpAmb>2</tpAmb>
        <dhEmi>{$dataEmissao}</dhEmi>
        <verAplic>1.0.0</verAplic>
        <serie>{$serieFormatada}</serie>
        <nDPS>{$numero}</nDPS>
        <dCompet>{$dataCompetencia}</dCompet>

        <tpEmit>1</tpEmit>
        <cLocEmi>{$codMun}</cLocEmi>

        <prest>
            <CNPJ>{$cnpjFormatado}</CNPJ>
            <IM>{$imLimpa}</IM>

            <regTrib>
                <opSimpNac>3</opSimpNac>
                <regApTribSN>1</regApTribSN>
                <regEspTrib>0</regEspTrib>
            </regTrib>
        </prest>

        <toma>
            <CNPJ>00000000000191</CNPJ>
            <xNome>TOMADOR TESTE DE HOMOLOGACAO</xNome>
        </toma>

        <serv>
            <locPrest>
                <cLocPrestacao>{$codMun}</cLocPrestacao>
            </locPrest>

            <cServ>
                <cTribNac>010601</cTribNac>
                <cTribMun>100</cTribMun>
                <xDescServ>Teste de Emissao API Nacional - G2m Fiscal</xDescServ>
            </cServ>
        </serv>

        <valores>
            <vServPrest>
                <vServ>10.00</vServ>
            </vServPrest>

            <trib>
                <tribMun>
                    <tribISSQN>1</tribISSQN>
                    <tpRetISSQN>1</tpRetISSQN>
                </tribMun>

                <totTrib>
                    <vTotTrib>
                        <vTotTribFed>0.00</vTotTribFed>
                        <vTotTribEst>0.00</vTotTribEst>
                        <vTotTribMun>0.00</vTotTribMun>
                    </vTotTrib>

                    </totTrib>
            </trib>
        </valores>
    </infDPS>
</DPS>
XML;

        // =================================================================
        // 3. ASSINATURA DO XML
        // =================================================================
        $this->info("3. Assinando XML...");

        try {
            $xmlAssinado = Signer::sign(
                $certificate,
                $xml,
                'infDPS',
                'Id',
                OPENSSL_ALGO_SHA1,
                [false, false, null, null]
            );

            // Garante cabeçalho
            if (!str_starts_with($xmlAssinado, '<?xml')) {
                $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlAssinado;
            }
        } catch (\Exception $e) {
            $this->error("Erro na assinatura: " . $e->getMessage());
            return;
        }

        // =================================================================
        // 4. PAYLOAD E ENVIO
        // =================================================================
        $this->info("4. Preparando Payload...");
        $xmlGzip = gzencode(trim($xmlAssinado), 9);
        $xmlBase64 = base64_encode($xmlGzip);

        $url = config('services.nfse_nacional.url_sefin');
        $this->info("5. Enviando para: $url");

        try {
            $response = Http::withOptions([
                'cert' => $tempPemPath,
                'verify' => false,
                'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer']
            ])->post($url, ['dpsXmlGZipB64' => $xmlBase64]);

            if (file_exists($tempPemPath)) @unlink($tempPemPath);

            $status = $response->status();
            $body = $response->json();

            $this->line("------------------------------------------------");
            $this->info("HTTP STATUS: " . $status);

            if ($status == 200 || $status == 201) {
                $this->info("SUCESSO!");
                $this->info("Chave: " . ($body['chaveAcesso'] ?? ''));
            } else {
                $this->error("ERRO:");
                // Exibe JSON bonito se possível, senão texto bruto
                if ($body) {
                    $this->line(json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                } else {
                    $this->line($response->body());
                }
            }

        } catch (\Exception $e) {
            $this->error("FALHA: " . $e->getMessage());
        }
    }
}
