<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LancamentoContabilItem extends Model
{
    protected $table = 'lancamento_contabil_itens';

    protected $fillable = [
        'lancamento_id',
        'conta_id',
        'tipo',
        'valor',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
        ];
    }

    public function lancamento(): BelongsTo
    {
        return $this->belongsTo(LancamentoContabil::class, 'lancamento_id');
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(ContaContabil::class, 'conta_id');
    }
}
