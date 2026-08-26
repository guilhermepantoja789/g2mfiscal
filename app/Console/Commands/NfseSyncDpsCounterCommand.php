<?php

namespace App\Console\Commands;

use App\Services\NfseDpsCounterSync;
use Illuminate\Console\Command;

/**
 * Correção de nDPS para produção em hospedagem compartilhada.
 *
 *   php artisan nfse:sync-dps-counter --dry-run
 *   php artisan nfse:sync-dps-counter --force
 *
 * Depois: reemitir a nota rejeitada (ex.: #43) pela tela — o próximo nDPS sai acima do histórico.
 */
class NfseSyncDpsCounterCommand extends Command
{
    protected $signature = 'nfse:sync-dps-counter
        {--empresa= : ID da empresa (omitir = todas)}
        {--dry-run : Só mostra o que mudaria, não grava}
        {--force : Roda sem confirmação (SSH/cron compartilhado)}';

    protected $description = 'Backfill de numero_dps e sincroniza nfse_dps_ultimo_numero (sem carregar XML). Use --force em produção.';

    public function handle(NfseDpsCounterSync $sync): int
    {
        $empresaId = $this->option('empresa') !== null && $this->option('empresa') !== ''
            ? (int) $this->option('empresa')
            : null;
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->option('force')) {
            if (! $this->confirm('Vai gravar numero_dps e o contador das empresas. Continuar?')) {
                $this->info('Cancelado.');

                return self::SUCCESS;
            }
        }

        $this->info($dryRun ? 'Dry-run: nenhuma alteração será gravada.' : 'Aplicando correção de nDPS…');

        $result = $sync->sync($empresaId, $dryRun);

        $this->newLine();
        $this->info('Notas com numero_dps nulo a preencher (id histórico): '.$result['backfilled']);

        $this->table(
            ['Empresa', 'Razão social', 'Contador antes', 'Contador depois', 'Próximo nDPS'],
            array_map(fn (array $row) => [
                $row['empresa_id'],
                $row['razao_social'],
                $row['antes'],
                $row['depois'],
                $row['depois'] + 1,
            ], $result['counters'])
        );

        if ($dryRun) {
            $this->comment('Para aplicar: php artisan nfse:sync-dps-counter --force');
        } else {
            $this->info('Pronto. Reemita a nota rejeitada pela tela (Emitir na Sefin).');
            $this->comment('A nota #43 deve receber nDPS novo (acima do histórico), não o 2.');
        }

        return self::SUCCESS;
    }
}
