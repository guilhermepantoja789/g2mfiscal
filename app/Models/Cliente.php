<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'razao_social',
        'cnpj',
        'inscricao_municipal', // Novo
        'email',
        'telefone',            // Novo
        'cep',
        'logradouro',
        'numero',
        'complemento',         // Novo
        'bairro',
        'uf',
        'cidade_codigo'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
