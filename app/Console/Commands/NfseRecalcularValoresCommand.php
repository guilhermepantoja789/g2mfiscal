<?php

namespace App\Console\Commands;

use App\Models\NotaFiscal;
use Illuminate\Console\Command;

/**
 * Recalcula valor_iss / valor_liquido em lotes pequenos (hospedagem compartilhada).
 *
 *   php artisan nfse:recalcular-valores --dry-run
 *   php artisan nfse:recalcular-valores --force
 */
class NfseRecalcularValoresCommand extends Command
{
    protected $signature = 'nfse:recalcular-valores
        {--empresa= : ID da empresa (omitir = todas)}
        {--dry-run : Só conta o que mudaria}
        {--force : Roda sem confirmação (SSH/cron compartilhado)}';

    protected $description = 'Recalcula ISS e valor líquido das NFS-e em lotes de 50, sem carregar XML.';

    public function handle(): int
    {
        $empresaId = $this->option('empresa') !== null && $this->option('empresa') !== ''
            ? (int) $this->option('empresa')
            : null;
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->option('force')) {
            if (! $this->confirm('Vai atualizar valor_iss e valor_liquido das notas. Continuar?')) {
                $this->info('Cancelado.');

                return self::SUCCESS;
            }
        }

        $query = NotaFiscal::query()
            ->select([
                'id',
                'empresa_id',
                'valor_servico',
                'aliquota_iss',
                'p_tot_trib_mun',
                'tp_ret_issqn',
                'valor_iss',
                'valor_liquido',
            ])
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('id');

        $analisadas = 0;
        $alteradas = 0;

        $query->chunkById(50, function ($notas) use ($dryRun, &$analisadas, &$alteradas) {
            foreach ($notas as $nota) {
                $analisadas++;
                $valores = $nota->valoresCalculados();
                $issAtual = round((float) $nota->valor_iss, 2);
                $liqAtual = round((float) $nota->valor_liquido, 2);

                if ($issAtual === $valores['valor_iss'] && $liqAtual === $valores['valor_liquido']) {
                    continue;
                }

                $alteradas++;
                if (! $dryRun) {
                    NotaFiscal::query()->whereKey($nota->id)->update($valores);
                }
            }
        });

        $this->info("Analisadas: {$analisadas}");
        $this->info(($dryRun ? 'Mudariam: ' : 'Atualizadas: ').$alteradas);

        if ($dryRun) {
            $this->comment('Para aplicar: php artisan nfse:recalcular-valores --force');
        }

        return self::SUCCESS;
    }
}
