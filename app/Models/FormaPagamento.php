<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormaPagamento extends Model
{
    protected $table = 'formas_pagamento';

    public const TIPO_AVISTA = 'avista';

    public const TIPO_PRAZO = 'prazo';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nome',
        'ativo',
        'tipo_liquidacao',
        'dias_recebimento',
        'parcelas',
        'juros_percentual',
        'gera_lancamento',
        'conta_contabil_id',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'gera_lancamento' => 'boolean',
            'dias_recebimento' => 'integer',
            'parcelas' => 'integer',
            'juros_percentual' => 'decimal:4',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function contaContabil(): BelongsTo
    {
        return $this->belongsTo(ContaContabil::class, 'conta_contabil_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoComercial::class);
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(LancamentoFinanceiro::class);
    }

    public function documentoPagamentos(): HasMany
    {
        return $this->hasMany(DocumentoPagamento::class);
    }

    public function isAvista(): bool
    {
        return $this->tipo_liquidacao === self::TIPO_AVISTA;
    }

    public function isPrazo(): bool
    {
        return $this->tipo_liquidacao === self::TIPO_PRAZO;
    }

    /**
     * Valor total com juros aplicados sobre a base.
     */
    public function valorComJuros(float $base): float
    {
        $juros = (float) $this->juros_percentual;
        if ($juros <= 0) {
            return round($base, 2);
        }

        return round($base * (1 + ($juros / 100)), 2);
    }
}
