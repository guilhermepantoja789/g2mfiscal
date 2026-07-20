<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificado extends Model
{
    use HasFactory;

    // Removemos 'serial' e trocamos 'validade' por 'valido_ate'
    protected $fillable = [
        'empresa_id',
        'nome_arquivo',
        'nome_original',
        'senha',
        'valido_ate',
        'ativo',
    ];

    protected $casts = [
        'valido_ate' => 'datetime', // <--- Cast correto
        'ativo' => 'boolean',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
