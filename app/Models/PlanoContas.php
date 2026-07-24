<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanoContas extends Model
{
    protected $table = 'planos_contas';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nome',
        'tipo',
        'natureza',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function contas(): HasMany
    {
        return $this->hasMany(ContaContabil::class, 'plano_id');
    }
}
