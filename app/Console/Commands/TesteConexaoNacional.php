<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Empresa;
use Illuminate\Support\Facades\Http;

class TesteConexaoNacional extends Command
{
    protected $signature = 'teste:nacional {empresa_id}';
    protected $description = 'Testa conexão com a API Nacional NFS-e convertendo PFX para PEM';

    public function handle()
    {
        $id = $this->argument('empresa_id');
        $empresa = Empresa::find($id);

        if (!$empresa || !$empresa->certificado) {
            $this->error("Empresa ou certificado não encontrados.");
            return;
        }

        $this->info("1. Preparando certificado...");

        try {
            $result = app(\App\Services\CertificadoA1Service::class)
                ->loadFromModel($empresa->certificado);
            $tempPemPath = tempnam(sys_get_temp_dir(), 'cert_nacional_') . '.pem';
            file_put_contents($tempPemPath, $result->toPem());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return;
        }

        $this->info("   Certificado convertido para PEM temporário: $tempPemPath");

        // --- CONEXÃO ---

        // URL de Produção Restrita (Sandbox)
        $baseUrl = \App\Services\NfseAmbiente::urlAdn().'/contribuintes';

        // Manaus: 1302603
        $codigoMunicipio = '1302603';

        $this->info("2. Iniciando conexão mTLS com a API Nacional...");

        try {
            // Faz a requisição usando o arquivo PEM temporário
            $response = Http::withOptions([
                'cert' => $tempPemPath, // Caminho para o arquivo PEM gerado
                'verify' => false,      // Ignora validação da cadeia SSL do servidor (Sandbox)
            ])->get("$baseUrl/parametros_municipais/$codigoMunicipio/convenio");

            // --- LIMPEZA ---
            // Apaga o arquivo temporário para não deixar lixo
            if (file_exists($tempPemPath)) {
                unlink($tempPemPath);
            }

            // --- RESULTADO ---
            if ($response->successful()) {
                $this->info("SUCESSO ABSOLUTO: Conexão estabelecida!");
                $this->line("Status Code: " . $response->status());
                $this->line("Resposta da API (Parâmetros do Município):");
                $this->line(substr($response->body(), 0, 500) . "..."); // Mostra os primeiros 500 caracteres
            } else {
                $this->error("FALHA NA REQUISIÇÃO (Mas conectou!)");
                $this->error("Status Code: " . $response->status());
                $this->error("Erro: " . $response->body());

                // Análise de erros comuns
                if ($response->status() == 403) {
                    $this->warn("Diagnóstico: Erro 403 significa que o certificado foi aceito, mas você não tem permissão para consultar este município ou ele não está ativo no Sandbox.");
                }
                if ($response->status() == 404) {
                    $this->warn("Diagnóstico: A URL está correta, mas o recurso não existe (talvez código IBGE errado?).");
                }
            }

        } catch (\Exception $e) {
            // Garante a limpeza mesmo com erro
            if (file_exists($tempPemPath)) unlink($tempPemPath);

            $this->error("ERRO CRÍTICO DE CONEXÃO: " . $e->getMessage());
        }
    }
}
