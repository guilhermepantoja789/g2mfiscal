<?php

namespace Database\Seeders;

use App\Models\ClassTrib;
use App\Models\IndOp;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IbscbsCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedIndOps();
        $this->seedClassTribs();
    }

    private function seedIndOps(): void
    {
        $csvFile = database_path('seeders/data/ind_ops.csv');
        if (! file_exists($csvFile)) {
            $this->command?->error("Arquivo não encontrado: {$csvFile}");

            return;
        }

        DB::table('ind_ops')->delete();

        $handle = fopen($csvFile, 'r');
        fgetcsv($handle); // header

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2 || empty($row[0])) {
                continue;
            }
            $batch[] = [
                'codigo' => str_pad(preg_replace('/\D/', '', $row[0]), 6, '0', STR_PAD_LEFT),
                'descricao' => mb_substr(trim($row[1]), 0, 255),
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        fclose($handle);

        foreach (array_chunk($batch, 100) as $chunk) {
            IndOp::query()->insert($chunk);
        }

        $this->command?->info('IndOp: '.count($batch).' códigos importados.');
    }

    private function seedClassTribs(): void
    {
        $csvFile = database_path('seeders/data/class_tribs.csv');
        if (! file_exists($csvFile)) {
            $this->command?->error("Arquivo não encontrado: {$csvFile}");

            return;
        }

        DB::table('class_tribs')->delete();

        $handle = fopen($csvFile, 'r');
        fgetcsv($handle);

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3 || empty($row[0]) || empty($row[1])) {
                continue;
            }
            $batch[] = [
                'cst' => str_pad(preg_replace('/\D/', '', $row[0]), 3, '0', STR_PAD_LEFT),
                'c_class_trib' => str_pad(preg_replace('/\D/', '', $row[1]), 6, '0', STR_PAD_LEFT),
                'descricao' => mb_substr(trim($row[2]), 0, 255),
                'ativo' => ! isset($row[3]) || $row[3] === '' || (bool) $row[3],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        fclose($handle);

        foreach (array_chunk($batch, 100) as $chunk) {
            ClassTrib::query()->insert($chunk);
        }

        $this->command?->info('ClassTrib: '.count($batch).' códigos importados.');
    }
}
