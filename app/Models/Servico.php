<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servico extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'nome',
        'codigo_interno',
        'codigo_tributacao_nacional',
        'codigo_tributacao_municipal',
        'codigo_nbs',
        'descricao',
        'valor_unitario',
        'iss_retido',
        'aliquota_iss',
        'aliquota_pis',
        'aliquota_cofins',
        'aliquota_inss',
        'aliquota_ir',
        'aliquota_csll',
    ];

    protected $casts = [
        'valor_unitario' => 'decimal:2',
        'iss_retido' => 'boolean',
        'aliquota_iss' => 'decimal:2',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
