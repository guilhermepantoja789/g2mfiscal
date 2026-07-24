<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoItem extends Model
{
    protected $table = 'documento_itens';

    protected $fillable = [
        'documento_comercial_id',
        'produto_id',
        'servico_id',
        'descricao',
        'ncm',
        'cfop',
        'csosn',
        'unidade',
        'codigo_fornecedor',
        'ean',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'ordem',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:4',
            'valor_unitario' => 'decimal:4',
            'valor_total' => 'decimal:2',
            'ordem' => 'integer',
        ];
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(DocumentoComercial::class, 'documento_comercial_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }
}
