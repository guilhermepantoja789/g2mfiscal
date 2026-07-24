<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LancamentoContabil extends Model
{
    protected $table = 'lancamentos_contabeis';

    public const STATUS_RASCUNHO = 'rascunho';

    public const STATUS_LANCADO = 'lancado';

    protected $fillable = [
        'empresa_id',
        'periodo_id',
        'data',
        'historico',
        'origem_tipo',
        'origem_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoContabil::class, 'periodo_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(LancamentoContabilItem::class, 'lancamento_id');
    }
}
