<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cobranca;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VerificarCobrancasVencidas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fiscal:verificar-cobrancas-vencidas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica cobranças pendentes vencidas e atualiza status para OVERDUE';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoje = Carbon::today();
        $this->info("=== Iniciando verificação de vencimentos: " . $hoje->format('d/m/Y') . " ===");

        // Busca cobranças PENDING com vencimento menor que hoje
        $vencidas = Cobranca::where('status', 'PENDING')
            ->whereDate('vencimento', '<', $hoje)
            ->get();

        if ($vencidas->isEmpty()) {
            $this->info("Nenhuma cobrança vencida encontrada.");
            return;
        }

        $count = 0;
        foreach ($vencidas as $cobranca) {
            try {
                $cobranca->update(['status' => 'OVERDUE']);
                $this->info("   -> Cobrança #{$cobranca->id} (R$ {$cobranca->valor}) marcada como OVERDUE.");
                
                // TODO: Futuramente, aqui enviaríamos notificação ao cliente
                
                $count++;
            } catch (\Exception $e) {
                Log::error("Erro ao atualizar cobrança vencida {$cobranca->id}: " . $e->getMessage());
                $this->error("   -> Erro ao atualizar cobrança {$cobranca->id}");
            }
        }

        $this->info("=== Finalizado. {$count} cobranças atualizadas. ===");
    }
}
