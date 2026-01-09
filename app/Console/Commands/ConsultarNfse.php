<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Empresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use NFePHP\Common\Certificate;

class ConsultarNfse extends Command
{
    protected $signature = 'teste:consultar_nfse {empresa_id} {chave}';
    protected $description = 'Consulta o XML de uma NFS-e pela Chave de Acesso (Homologação)';

    public function handle()
    {
        $id = $this->argument('empresa_id');
        $chave = $this->argument('chave');

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

            $certs = [];
            if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
                $this->error("Erro ao ler PFX."); return;
            }
            $pemContent = $certs['cert'] . "\n" . $certs['pkey'];
            $tempPemPath = tempnam(sys_get_temp_dir(), 'cert_cons_') . '.pem';
            file_put_contents($tempPemPath, $pemContent);
        } catch (\Exception $e) {
            $this->error($e->getMessage()); return;
        }

        // =================================================================
        // 2. MONTAGEM DA URL (SIMPLIFICADA)
        // =================================================================
        // Base URL de Homologação
        $baseUrl = config('services.nfse_nacional.url_sefin');

        // Apenas a chave de acesso, conforme sua correção
        $url = "{$baseUrl}/{$chave}";

        $this->info("2. Consultando: $url");

        // =================================================================
        // 3. REQUISIÇÃO GET
        // =================================================================
        try {
            $response = Http::withOptions([
                'cert' => $tempPemPath,
                'verify' => false,
                'timeout' => 90,
            ])->get($url);

            @unlink($tempPemPath);

            $status = $response->status();
            $body = $response->json(); // Tenta ler como JSON

            $this->line("------------------------------------------------");
            $this->info("HTTP STATUS: " . $status);

            if ($status == 200) {
                // Verifica se veio o XML zipado (padrão) ou outro formato
                $xmlBase64 = $body['nfs-eXmlGZipB64']
                    ?? $body['eventoXmlGZipB64']
                    ?? $body['nfseXmlGZipB64']
                    ?? null;

                if ($xmlBase64) {
                    $xmlGzip = base64_decode($xmlBase64);
                    $xml = gzdecode($xmlGzip);

                    $this->info("SUCESSO! XML DA NOTA RECUPERADO:");
                    $this->line("------------------------------------------------");

                    // Formata para exibição
                    $dom = new \DOMDocument;
                    $dom->preserveWhiteSpace = false;
                    $dom->formatOutput = true;
                    if ($dom->loadXML($xml)) {
                        echo $dom->saveXML();
                    } else {
                        echo $xml;
                    }
                    $this->line("\n------------------------------------------------");
                } else {
                    // Caso o campo tenha nome diferente, exibe o JSON inteiro para debug
                    $this->warn("Campo de XML não encontrado pelo nome padrão. Resposta bruta:");
                    $this->line(json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            } else {
                $this->error("Erro na consulta:");
                $this->line($response->body());
            }

        } catch (\Exception $e) {
            $this->error("Falha na conexão: " . $e->getMessage());
        }
    }
}
