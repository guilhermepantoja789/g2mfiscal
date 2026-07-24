<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndOp extends Model
{
    protected $fillable = [
        'codigo',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];
}
