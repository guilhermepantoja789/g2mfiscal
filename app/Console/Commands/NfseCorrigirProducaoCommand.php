<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Roda as correções NFS-e em sequência (uma chamada SSH na hospedagem compartilhada).
 *
 *   php artisan nfse:corrigir-producao --dry-run
 *   php artisan nfse:corrigir-producao --force
 *
 * Depois reemitir a nota rejeitada (ex.: #43) em Emitir na Sefin.
 */
class NfseCorrigirProducaoCommand extends Command
{
    protected $signature = 'nfse:corrigir-producao
        {--empresa= : ID da empresa (omitir = todas)}
        {--dry-run : Só simula}
        {--force : Sem confirmação (produção compartilhada)}';

    protected $description = 'Sincroniza nDPS e recalcula valor líquido. Preferir este comando em produção.';

    public function handle(): int
    {
        $params = [
            '--dry-run' => $this->option('dry-run'),
            '--force' => $this->option('force') || $this->option('dry-run'),
        ];
        if ($this->option('empresa') !== null && $this->option('empresa') !== '') {
            $params['--empresa'] = $this->option('empresa');
        }

        $this->info('1/2 nDPS…');
        $dps = $this->call('nfse:sync-dps-counter', $params);
        if ($dps !== self::SUCCESS) {
            return $dps;
        }

        $this->newLine();
        $this->info('2/2 valores…');

        return $this->call('nfse:recalcular-valores', $params);
    }
}
