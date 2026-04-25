<?php

namespace App\Console\Commands;

use App\Models\NotaFiscal;
use Illuminate\Console\Command;

class CorrigirTributosNotas extends Command
{
    protected $signature = 'notas:corrigir-tributos {--dry-run : Simula sem alterar o banco}';

    protected $description = 'Corrige notas onde % está preenchida mas valor está zerado (ou vice-versa). Ignora notas com tp_ret_issqn = 1 e ambos zerados.';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔎 MODO DRY-RUN: Nenhuma alteração será feita no banco.');
        } else {
            if (!$this->confirm('⚠️  Isso vai alterar dados em produção. Deseja continuar?')) {
                $this->info('Operação cancelada.');
                return 0;
            }
        }

        $pares = [
            ['p_tot_trib_fed', 'v_tot_trib_fed', 'Federal'],
            ['p_tot_trib_est', 'v_tot_trib_est', 'Estadual'],
            ['p_tot_trib_mun', 'v_tot_trib_mun', 'Municipal'],
        ];

        $totalCorrigidas = 0;
        $totalAnalisadas = 0;

        // Busca notas que NÃO são tp_ret_issqn = 1 (sem retenção)
        // OU qualquer nota que tenha pelo menos um par inconsistente
        $notas = NotaFiscal::where(function ($query) use ($pares) {
            foreach ($pares as [$campoPct, $campoVal, $label]) {
                $query->orWhere(function ($q) use ($campoPct, $campoVal) {
                    // % > 0 mas valor = 0
                    $q->where($campoPct, '>', 0)->where(function ($inner) use ($campoVal) {
                        $inner->where($campoVal, 0)->orWhereNull($campoVal);
                    });
                })->orWhere(function ($q) use ($campoPct, $campoVal) {
                    // valor > 0 mas % = 0
                    $q->where($campoVal, '>', 0)->where(function ($inner) use ($campoPct) {
                        $inner->where($campoPct, 0)->orWhereNull($campoPct);
                    });
                });
            }
        })->get();

        $this->info("📋 Encontradas {$notas->count()} nota(s) com possíveis inconsistências.");
        $this->newLine();

        foreach ($notas as $nota) {
            $totalAnalisadas++;
            $alteracoes = [];
            $valorServico = (float)$nota->valor_servico;

            if ($valorServico <= 0) {
                $this->warn("  ⏭  Nota #{$nota->id} (NFS-e {$nota->numero_nfse}) - valor_servico = 0, ignorada.");
                continue;
            }

            foreach ($pares as [$campoPct, $campoVal, $label]) {
                $pct = (float)($nota->$campoPct ?? 0);
                $val = (float)($nota->$campoVal ?? 0);

                if ($pct > 0 && $val == 0) {
                    // Tem % mas falta valor → calcular valor
                    $novoVal = round($valorServico * $pct / 100, 2);
                    $alteracoes[$campoVal] = $novoVal;
                    $this->line("  📊 {$label}: {$pct}% → R$ {$novoVal} (calculado)");
                } elseif ($val > 0 && $pct == 0) {
                    // Tem valor mas falta % → calcular %
                    $novaPct = round(($val / $valorServico) * 100, 2);
                    $alteracoes[$campoPct] = $novaPct;
                    $this->line("  📊 {$label}: R$ {$val} → {$novaPct}% (calculado)");
                }
            }

            if (!empty($alteracoes)) {
                $totalCorrigidas++;
                $notaLabel = $nota->numero_nfse ? "NFS-e #{$nota->numero_nfse}" : "ID #{$nota->id}";
                $this->info("  ✏️  {$notaLabel} | Tomador: {$nota->tomador_nome} | Valor: R$ " . number_format($valorServico, 2, ',', '.'));

                if (!$dryRun) {
                    $nota->update($alteracoes);
                    $this->info("  ✅ Corrigida!");
                } else {
                    $this->comment("  [DRY-RUN] Seria corrigida: " . json_encode($alteracoes));
                }
                $this->newLine();
            }
        }

        $this->newLine();
        $this->info("═══════════════════════════════════════");
        $this->info("📊 Resumo:");
        $this->info("   Analisadas: {$totalAnalisadas}");
        $this->info("   Corrigidas: {$totalCorrigidas}");
        $this->info("═══════════════════════════════════════");

        if ($dryRun && $totalCorrigidas > 0) {
            $this->newLine();
            $this->warn("💡 Para aplicar as correções, rode sem --dry-run:");
            $this->warn("   php artisan notas:corrigir-tributos");
        }

        return 0;
    }
}
