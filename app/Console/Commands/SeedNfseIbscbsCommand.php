<?php

namespace App\Console\Commands;

use Database\Seeders\BackfillServicoIbscbsSeeder;
use Database\Seeders\IbscbsCatalogSeeder;
use Illuminate\Console\Command;

class SeedNfseIbscbsCommand extends Command
{
    protected $signature = 'nfse:seed-ibscbs
                            {--backfill : Também preenche defaults IBS/CBS nos serviços existentes (não preenche NBS)}';

    protected $description = 'Importa catálogos IndOp/ClassTrib/NBS/correlação (IBS/CBS) e opcionalmente faz backfill nos serviços';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => IbscbsCatalogSeeder::class,
            '--force' => true,
        ]);

        if ($this->option('backfill')) {
            $this->call('db:seed', [
                '--class' => BackfillServicoIbscbsSeeder::class,
                '--force' => true,
            ]);
        }

        $this->info('Catálogos IBS/CBS + NBS atualizados.');

        return self::SUCCESS;
    }
}
