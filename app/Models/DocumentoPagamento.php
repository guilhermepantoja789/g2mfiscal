<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoPagamento extends Model
{
    protected $table = 'documento_pagamentos';

    protected $fillable = [
        'documento_comercial_id',
        'forma_pagamento_id',
        'valor',
        'v_troco',
        'ordem',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'v_troco' => 'decimal:2',
            'ordem' => 'integer',
        ];
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(DocumentoComercial::class, 'documento_comercial_id');
    }

    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(FormaPagamento::class, 'forma_pagamento_id');
    }
}
