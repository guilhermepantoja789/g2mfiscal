<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NbsCode extends Model
{
    protected $fillable = [
        'codigo',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function getLabelAttribute(): string
    {
        return "{$this->codigo} — {$this->descricao}";
    }
}
