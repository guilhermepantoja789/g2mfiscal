<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Empresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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

        // 1. Verifica se o arquivo existe usando a abstração do Storage (funciona em private/)
        if (!Storage::exists($empresa->certificado->nome_arquivo)) {
            $this->error("Arquivo não encontrado no Storage (caminho: {$empresa->certificado->nome_arquivo})");
            return;
        }

        // 2. Lê o conteúdo binário do PFX (independente da pasta física)
        $pfxContent = Storage::get($empresa->certificado->nome_arquivo);

        // 3. Extrai as chaves (Certificado + Private Key)
        $certs = [];
        if (!openssl_pkcs12_read($pfxContent, $certs, $empresa->certificado->senha)) {
            $this->error("Erro ao ler PFX. Senha incorreta ou formato inválido.");
            return;
        }

        // 4. Cria o conteúdo PEM combinando a chave e o certificado
        // O Guzzle prefere receber um único arquivo PEM contendo tudo
        $pemContent = $certs['cert'] . "\n" . $certs['pkey'];

        // 5. Salva um arquivo temporário na pasta /tmp do sistema
        // Isso evita problemas de permissão e path do Laravel
        $tempPemPath = tempnam(sys_get_temp_dir(), 'cert_nacional_') . '.pem';
        file_put_contents($tempPemPath, $pemContent);

        $this->info("   Certificado convertido para PEM temporário: $tempPemPath");

        // --- CONEXÃO ---

        // URL de Produção Restrita (Sandbox)
        $baseUrl = config('services.nfse_nacional.url_adn') . '/contribuintes';

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
