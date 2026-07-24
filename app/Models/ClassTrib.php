<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassTrib extends Model
{
    protected $fillable = [
        'cst',
        'c_class_trib',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function getLabelAttribute(): string
    {
        return "{$this->cst}/{$this->c_class_trib} — {$this->descricao}";
    }
}
