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
        'codigo_tributacao_nacional', // Ex: 1.03.01
        'codigo_tributacao_municipal', // Ex: 1234
        'codigo_nbs',
        'descricao',
        'valor_unitario',
    ];

    protected $casts = [
        'valor_unitario' => 'decimal:2',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
