<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TributacaoNacional;
use Illuminate\Support\Facades\DB;

class TributacaoNacionalSeeder extends Seeder
{
    public function run()
    {
        // 1. Limpa a tabela antes de importar
        DB::table('tributacao_nacionals')->truncate();

        // 2. Caminho do arquivo
        $csvFile = database_path('seeders/data/lista_servicos.csv');

        if (!file_exists($csvFile)) {
            $this->command->error("Arquivo não encontrado: $csvFile");
            $this->command->warn("Crie a pasta 'database/seeders/data' e coloque o arquivo 'lista_servicos.csv' lá.");
            return;
        }

        // 3. Abre o arquivo
        $handle = fopen($csvFile, "r");

        // Pula o cabeçalho
        fgetcsv($handle, 1000, ";");

        $batch = [];
        $count = 0;

        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            // Se a linha estiver vazia ou com erro de leitura, pula
            if (count($data) < 5) continue;

            // Mapeamento das colunas (Baseado no seu snippet):
            // [0] => CÓDIGO (ex: 010101)
            // [1] => ITEM (01)
            // [2] => SUBITEM (01)
            // [3] => DESDOBRO (01)
            // [4] => DESCRIÇÃO

            $codigoRaw = $data[0];

            // Pula linhas que não têm código (títulos de agrupamento)
            if (empty($codigoRaw)) continue;

            // Tratamento de Encodificação (Resolve problemas de acentos do Excel)
            $descricao = $data[4];
            if (!mb_detect_encoding($descricao, 'UTF-8', true)) {
                $descricao = mb_convert_encoding($descricao, 'UTF-8', 'Windows-1252');
            }

            // Tratamento do Código (Apenas números, 6 dígitos)
            $codigoLimpo = preg_replace('/[^0-9]/', '', $codigoRaw);
            $codigoFinal = str_pad($codigoLimpo, 6, '0', STR_PAD_LEFT);

            // Monta o Item da LC 116 (Ex: "1.01")
            // Intval no primeiro remove o zero à esquerda (01 -> 1)
            $itemLc = intval($data[1]) . '.' . str_pad($data[2], 2, '0', STR_PAD_LEFT);

            $batch[] = [
                'codigo'      => $codigoFinal,
                'item_lc116'  => $itemLc,
                'descricao'   => trim($descricao),
                'created_at'  => now(),
                'updated_at'  => now(),
            ];

            // Insere em lotes de 200 para performance
            if (count($batch) >= 200) {
                TributacaoNacional::insert($batch);
                $batch = [];
            }
            $count++;
        }

        // Insere o restante
        if (!empty($batch)) {
            TributacaoNacional::insert($batch);
        }

        fclose($handle);
        $this->command->info("Importação concluída! $count serviços cadastrados com sucesso.");
    }
}
