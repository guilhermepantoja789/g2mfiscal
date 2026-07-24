<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstoqueMovimentacao extends Model
{
    protected $table = 'estoque_movimentacoes';

    public const TIPO_ENTRADA = 'entrada';

    public const TIPO_SAIDA = 'saida';

    public const TIPO_AJUSTE = 'ajuste';

    protected $fillable = [
        'empresa_id',
        'produto_id',
        'documento_comercial_id',
        'tipo',
        'quantidade',
        'custo_unitario',
        'saldo_apos',
        'origem',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:4',
            'custo_unitario' => 'decimal:4',
            'saldo_apos' => 'decimal:4',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(DocumentoComercial::class, 'documento_comercial_id');
    }
}
