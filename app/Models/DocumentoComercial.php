<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DocumentoComercial extends Model
{
    protected $table = 'documentos_comerciais';

    public const TIPO_VENDA = 'venda';

    public const TIPO_COMPRA = 'compra';

    public const CANAL_NFSE = 'nfse';

    public const CANAL_NFCE = 'nfce';

    public const CANAL_NFE_ENTRADA = 'nfe_entrada';

    public const STATUS_RASCUNHO = 'rascunho';

    public const STATUS_CONFIRMADO = 'confirmado';

    public const STATUS_PROCESSANDO_FISCAL = 'processando_fiscal';

    public const STATUS_AUTORIZADO = 'autorizado';

    public const STATUS_ERRO = 'erro';

    public const STATUS_CANCELADO = 'cancelado';

    protected $fillable = [
        'empresa_id',
        'tipo',
        'canal_fiscal',
        'status',
        'cliente_id',
        'fornecedor_id',
        'valor_total',
        'data_competencia',
        'forma_pagamento',
        'forma_pagamento_id',
        'vencimento',
        'pago_avista',
        'chave_nfe',
        'xml_nfe',
        'numero_nfe',
        'serie_nfe',
        'observacoes',
        'mensagem_erro',
    ];

    protected function casts(): array
    {
        return [
            'valor_total' => 'decimal:2',
            'data_competencia' => 'date',
            'vencimento' => 'date',
            'pago_avista' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function formaPagamentoRel(): BelongsTo
    {
        return $this->belongsTo(FormaPagamento::class, 'forma_pagamento_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(DocumentoItem::class)->orderBy('ordem');
    }

    public function notaFiscal(): HasOne
    {
        return $this->hasOne(NotaFiscal::class);
    }

    public function nfce(): HasOne
    {
        return $this->hasOne(Nfce::class);
    }

    public function movimentacoesEstoque(): HasMany
    {
        return $this->hasMany(EstoqueMovimentacao::class);
    }

    public function lancamentosFinanceiros(): HasMany
    {
        return $this->hasMany(LancamentoFinanceiro::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RASCUNHO => 'Rascunho',
            self::STATUS_CONFIRMADO => 'Confirmado',
            self::STATUS_PROCESSANDO_FISCAL => 'Processando fiscal',
            self::STATUS_AUTORIZADO => 'Autorizado',
            self::STATUS_ERRO => 'Erro',
            self::STATUS_CANCELADO => 'Cancelado',
            default => ucfirst((string) $this->status),
        };
    }

    public function isRascunho(): bool
    {
        return $this->status === self::STATUS_RASCUNHO;
    }

    public function isVenda(): bool
    {
        return $this->tipo === self::TIPO_VENDA;
    }

    public function isCompra(): bool
    {
        return $this->tipo === self::TIPO_COMPRA;
    }
}
