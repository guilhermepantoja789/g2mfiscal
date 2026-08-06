<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NbsCorrelacao extends Model
{
    protected $table = 'nbs_correlacoes';

    protected $fillable = [
        'c_trib_nac',
        'codigo_nbs',
        'c_ind_op',
        'cst',
        'c_class_trib',
        'escopo',
    ];

    /**
     * Sugestões do Anexo VIII para um cTribNac (orientação UX, não regra SEFIN).
     *
     * @return array{nbs: list<array{codigo: string, c_ind_op: string, cst: string, c_class_trib: string, escopo: string}>, ind_ops: list<string>, class_pairs: list<string>}
     */
    public static function sugestoesPara(string $cTribNac, bool $priorizarTiManaus = true): array
    {
        $digits = str_pad(preg_replace('/\D/', '', $cTribNac) ?? '', 6, '0', STR_PAD_LEFT);

        $query = static::query()->where('c_trib_nac', $digits);
        if ($priorizarTiManaus) {
            $query->orderByRaw("CASE WHEN escopo = 'ti_manaus' THEN 0 ELSE 1 END");
        }
        $rows = $query->orderBy('codigo_nbs')->get();

        $nbs = [];
        $indOps = [];
        $pairs = [];
        foreach ($rows as $row) {
            $nbs[] = [
                'codigo' => $row->codigo_nbs,
                'c_ind_op' => $row->c_ind_op,
                'cst' => $row->cst,
                'c_class_trib' => $row->c_class_trib,
                'escopo' => $row->escopo,
            ];
            $indOps[] = (string) $row->c_ind_op;
            $pairs[] = $row->cst.'|'.$row->c_class_trib;
        }

        return [
            'nbs' => $nbs,
            'ind_ops' => array_values(array_unique($indOps)),
            'class_pairs' => array_values(array_unique($pairs)),
        ];
    }
}
