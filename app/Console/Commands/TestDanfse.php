<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Empresa;
use App\Services\NfseNacionalService;
use Illuminate\Support\Facades\Log;

class TestDanfse extends Command
{
    /**
     * O nome e assinatura do comando.
     * Ex: php artisan nfse:danfse 1 130260322...
     */
    protected $signature = 'nfse:danfse {empresa_id} {chave_acesso}';

    /**
     * A descrição do comando.
     */
    protected $description = 'Baixa o PDF (DANFSe) da Nacional e salva na pasta storage.';

    /**
     * Executa o comando.
     */
    public function handle()
    {
        $empresaId = $this->argument('empresa_id');
        $chave = $this->argument('chave_acesso');

        $this->info("Iniciando teste para Empresa ID: $empresaId");
        $this->info("Chave: $chave");

        try {
            // 1. Busca a empresa
            $empresa = Empresa::find($empresaId);
            if (!$empresa) {
                $this->error("Empresa não encontrada.");
                return 1;
            }

            $this->line("Empresa carregada: " . $empresa->razao_social);

            // 2. Instancia o serviço
            $service = new NfseNacionalService($empresa);

            // 3. Tenta baixar
            $this->line("Tentando baixar PDF via API...");

            // Aqui ele chama o método que criamos
            $pdfContent = $service->downloadDanfse($chave);

            // 4. Salva o arquivo para conferência
            $fileName = "danfse_{$chave}.pdf";
            $path = storage_path("app/{$fileName}");

            file_put_contents($path, $pdfContent);

            $this->info("SUCESSO! PDF baixado.");
            $this->info("Arquivo salvo em: $path");

            return 0;

        } catch (\Exception $e) {
            $this->error("ERRO ao baixar DANFSe:");
            $this->error($e->getMessage());

            // Mostra o log se houver detalhes
            if (method_exists($e, 'getResponse')) {
                $this->line("Body: " . $e->getResponse()->getBody()->getContents());
            }

            return 1;
        }
    }
}
