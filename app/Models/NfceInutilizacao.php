<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfceInutilizacao extends Model
{
    protected $table = 'nfce_inutilizacoes';

    protected $fillable = [
        'empresa_id',
        'serie',
        'numero_ini',
        'numero_fin',
        'ano',
        'ambiente',
        'protocolo',
        'c_stat',
        'x_motivo',
        'x_just',
        'xml_enviado',
        'xml_retorno',
    ];

    protected function casts(): array
    {
        return [
            'serie' => 'integer',
            'numero_ini' => 'integer',
            'numero_fin' => 'integer',
            'ano' => 'integer',
            'ambiente' => 'integer',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
