<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Empresa;

class DasPagamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'competencia',
        'valor_estimado',
        'valor_pago',
        'data_pagamento',
        'status',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
