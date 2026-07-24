<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    protected $fillable = [
        'empresa_id',
        'sku',
        'ean',
        'descricao',
        'ncm',
        'cfop',
        'csosn',
        'unidade',
        'preco_venda',
        'custo_medio',
        'estoque_atual',
        'controla_estoque',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'preco_venda' => 'decimal:2',
            'custo_medio' => 'decimal:4',
            'estoque_atual' => 'decimal:4',
            'controla_estoque' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(EstoqueMovimentacao::class);
    }
}
