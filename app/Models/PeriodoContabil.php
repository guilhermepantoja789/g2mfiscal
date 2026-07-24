<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoContabil extends Model
{
    protected $table = 'periodos_contabeis';

    public const STATUS_ABERTO = 'aberto';

    public const STATUS_FECHADO = 'fechado';

    protected $fillable = [
        'empresa_id',
        'competencia',
        'status',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(LancamentoContabil::class, 'periodo_id');
    }
}
