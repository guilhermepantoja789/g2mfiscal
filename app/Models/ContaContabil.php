<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaContabil extends Model
{
    protected $table = 'contas_contabeis';

    protected $fillable = [
        'empresa_id',
        'plano_id',
        'codigo',
        'nome',
        'tipo',
        'natureza',
        'conta_pai_id',
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

    public function plano(): BelongsTo
    {
        return $this->belongsTo(PlanoContas::class, 'plano_id');
    }

    public function pai(): BelongsTo
    {
        return $this->belongsTo(self::class, 'conta_pai_id');
    }

    public function filhos(): HasMany
    {
        return $this->hasMany(self::class, 'conta_pai_id');
    }
}
