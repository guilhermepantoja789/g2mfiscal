<?php

namespace App\Console\Commands;

use Database\Seeders\BackfillServicoIbscbsSeeder;
use Database\Seeders\IbscbsCatalogSeeder;
use Illuminate\Console\Command;

class SeedNfseIbscbsCommand extends Command
{
    protected $signature = 'nfse:seed-ibscbs {--backfill : Também preenche defaults nos serviços existentes}';

    protected $description = 'Importa catálogos IndOp/ClassTrib (IBS/CBS) e opcionalmente faz backfill nos serviços';

    public function handle(): int
    {
        $this->call(IbscbsCatalogSeeder::class);

        if ($this->option('backfill')) {
            $this->call(BackfillServicoIbscbsSeeder::class);
        }

        $this->info('Catálogos IBS/CBS atualizados.');

        return self::SUCCESS;
    }
}
