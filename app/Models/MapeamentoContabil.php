<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapeamentoContabil extends Model
{
    protected $table = 'mapeamentos_contabeis';

    protected $fillable = [
        'empresa_id',
        'origem',
        'conta_id',
        'metadados',
    ];

    protected function casts(): array
    {
        return [
            'metadados' => 'array',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(ContaContabil::class, 'conta_id');
    }
}
