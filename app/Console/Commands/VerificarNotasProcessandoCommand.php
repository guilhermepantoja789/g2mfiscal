<?php

namespace App\Console\Commands;

use App\Models\NotaFiscal;
use App\Services\NfseNacionalService;
use App\Services\FinanceiroService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerificarNotasProcessandoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notas:verificar-processando';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica notas presas no status processando há mais de 1 hora';

    /**
     * Execute the console command.
     */
    public function handle(FinanceiroService $financeiroService)
    {
        $notas = NotaFiscal::where('status', 'processando')
            ->where('updated_at', '<', Carbon::now()->subHour())
            ->get();

        $this->info("Encontradas {$notas->count()} notas presas em processamento.");

        foreach ($notas as $nota) {
            $this->info("Analisando nota {$nota->id}...");

            if ($nota->chave_acesso) {
                // Tenta consultar na Sefin
                try {
                    $service = new NfseNacionalService($nota->empresa);
                    $retorno = $service->consultarNota($nota->chave_acesso);

                    if ($retorno['sucesso'] && isset($retorno['xml_autorizado'])) {
                        $nota->update([
                            'status' => 'autorizada',
                            'xml_autorizado' => $retorno['xml_autorizado'],
                            'mensagem_erro' => null
                        ]);

                        if ($nota->cobranca) {
                            $financeiroService->ativarCobranca($nota->cobranca);
                        }
                        
                        $this->info("Nota {$nota->id} atualizada para autorizada.");
                        continue;
                    }
                } catch (\Exception $e) {
                    Log::error("Erro ao consultar nota travada {$nota->id}: " . $e->getMessage());
                }
            }

            // Se não tem chave ou a consulta falhou, reverte para erro
            $nota->update([
                'status' => 'erro',
                'mensagem_erro' => 'A nota demorou muito para processar e expirou o tempo limite local. Por favor, tente emitir novamente.'
            ]);
            $this->info("Nota {$nota->id} revertida para erro por timeout.");
        }

        $this->info('Finalizado.');
    }
}
