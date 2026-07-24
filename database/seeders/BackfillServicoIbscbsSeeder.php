<?php

namespace Database\Seeders;

use App\Models\Servico;
use Illuminate\Database\Seeder;

/**
 * Preenche defaults IBS/CBS em serviços ainda sem classificação.
 * Padrão Manaus / serviços típicos à distância: IndOp 100301 + CST 000/000001.
 */
class BackfillServicoIbscbsSeeder extends Seeder
{
    public const DEFAULT_C_IND_OP = '100301';

    public const DEFAULT_CST = '000';

    public const DEFAULT_C_CLASS_TRIB = '000001';

    public function run(): void
    {
        $updated = Servico::query()
            ->where(function ($q) {
                $q->whereNull('c_ind_op')
                    ->orWhereNull('cst_ibscbs')
                    ->orWhereNull('c_class_trib')
                    ->orWhere('c_ind_op', '')
                    ->orWhere('cst_ibscbs', '')
                    ->orWhere('c_class_trib', '');
            })
            ->update([
                'fin_nfse' => '0',
                'c_ind_op' => self::DEFAULT_C_IND_OP,
                'cst_ibscbs' => self::DEFAULT_CST,
                'c_class_trib' => self::DEFAULT_C_CLASS_TRIB,
            ]);

        $this->command?->info("Serviços atualizados com defaults IBSCBS: {$updated}");
    }
}
