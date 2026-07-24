<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LancamentoFinanceiro extends Model
{
    protected $table = 'lancamentos_financeiros';

    public const TIPO_RECEBER = 'receber';

    public const TIPO_PAGAR = 'pagar';

    public const STATUS_ABERTO = 'aberto';

    public const STATUS_PAGO = 'pago';

    public const STATUS_CANCELADO = 'cancelado';

    protected $fillable = [
        'empresa_id',
        'documento_comercial_id',
        'cliente_id',
        'fornecedor_id',
        'cobranca_id',
        'forma_pagamento_id',
        'parcela',
        'total_parcelas',
        'tipo',
        'status',
        'valor',
        'vencimento',
        'pago_em',
        'descricao',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'vencimento' => 'date',
            'pago_em' => 'datetime',
            'parcela' => 'integer',
            'total_parcelas' => 'integer',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(DocumentoComercial::class, 'documento_comercial_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function cobranca(): BelongsTo
    {
        return $this->belongsTo(Cobranca::class);
    }

    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(FormaPagamento::class, 'forma_pagamento_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ABERTO => 'Aberto',
            self::STATUS_PAGO => 'Pago',
            self::STATUS_CANCELADO => 'Cancelado',
            default => ucfirst((string) $this->status),
        };
    }
}
