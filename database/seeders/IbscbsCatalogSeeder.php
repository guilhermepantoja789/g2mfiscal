<?php

namespace Database\Seeders;

use App\Models\ClassTrib;
use App\Models\IndOp;
use App\Models\NbsCode;
use App\Models\NbsCorrelacao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogos NFS-e RTC (IBS/CBS + NBS).
 *
 * Fontes versionadas em database/seeders/data/ e docs/nfse-rtc/anexos/:
 * - IndOp: Anexo VII V1.00.00
 * - ClassTrib: IT 2025.002 / portal DF-e SVRS (+ destaque Manaus/TI)
 * - NBS: MDIC NBS 2.0 (folhas 9 dígitos)
 * - Correlação: Anexo VIII V1.01.00 (orientação UX)
 */
class IbscbsCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedIndOps();
        $this->seedClassTribs();
        $this->seedNbsCodes();
        $this->seedNbsCorrelacoes();
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
        fgetcsv($handle);

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
        $header = fgetcsv($handle);
        $hasDestaque = in_array('destaque', $header ?? [], true);

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3 || empty($row[0]) || empty($row[1])) {
                continue;
            }
            $destaque = $hasDestaque
                ? (! empty($row[4]) && (string) $row[4] !== '0')
                : false;

            $batch[] = [
                'cst' => str_pad(preg_replace('/\D/', '', $row[0]), 3, '0', STR_PAD_LEFT),
                'c_class_trib' => str_pad(preg_replace('/\D/', '', $row[1]), 6, '0', STR_PAD_LEFT),
                'descricao' => mb_substr(trim($row[2]), 0, 255),
                'ativo' => ! isset($row[3]) || $row[3] === '' || (bool) $row[3],
                'destaque' => $destaque,
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

    private function seedNbsCodes(): void
    {
        $csvFile = database_path('seeders/data/nbs_codes.csv');
        if (! file_exists($csvFile)) {
            $this->command?->warn("Arquivo não encontrado: {$csvFile}");

            return;
        }

        DB::table('nbs_codes')->delete();

        $handle = fopen($csvFile, 'r');
        fgetcsv($handle);

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2 || empty($row[0])) {
                continue;
            }
            $codigo = preg_replace('/\D/', '', $row[0]);
            if (strlen($codigo) !== 9) {
                continue;
            }
            $batch[] = [
                'codigo' => $codigo,
                'descricao' => mb_substr(trim($row[1]), 0, 500),
                'ativo' => ! isset($row[2]) || $row[2] === '' || (bool) $row[2],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        fclose($handle);

        foreach (array_chunk($batch, 200) as $chunk) {
            NbsCode::query()->insert($chunk);
        }

        $this->command?->info('NBS: '.count($batch).' códigos importados.');
    }

    private function seedNbsCorrelacoes(): void
    {
        $csvFile = database_path('seeders/data/nbs_correlacoes.csv');
        if (! file_exists($csvFile)) {
            $this->command?->warn("Arquivo não encontrado: {$csvFile}");

            return;
        }

        DB::table('nbs_correlacoes')->delete();

        $handle = fopen($csvFile, 'r');
        fgetcsv($handle);

        $batch = [];
        $seen = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 5 || empty($row[0]) || empty($row[1])) {
                continue;
            }
            $item = [
                'c_trib_nac' => str_pad(preg_replace('/\D/', '', $row[0]), 6, '0', STR_PAD_LEFT),
                'codigo_nbs' => preg_replace('/\D/', '', $row[1]),
                'c_ind_op' => str_pad(preg_replace('/\D/', '', $row[2]), 6, '0', STR_PAD_LEFT),
                'cst' => str_pad(preg_replace('/\D/', '', $row[3]), 3, '0', STR_PAD_LEFT),
                'c_class_trib' => str_pad(preg_replace('/\D/', '', $row[4]), 6, '0', STR_PAD_LEFT),
                'escopo' => in_array($row[5] ?? 'geral', ['ti_manaus', 'geral'], true) ? $row[5] : 'geral',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (strlen($item['codigo_nbs']) !== 9) {
                continue;
            }
            $key = $item['c_trib_nac'].'|'.$item['codigo_nbs'].'|'.$item['c_ind_op'].'|'.$item['c_class_trib'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $batch[] = $item;
        }
        fclose($handle);

        foreach (array_chunk($batch, 200) as $chunk) {
            NbsCorrelacao::query()->insert($chunk);
        }

        $this->command?->info('Correlações NBS: '.count($batch).' linhas importadas.');
    }
}
