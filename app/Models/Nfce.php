<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nfce extends Model
{
    protected $table = 'nfces';

    protected $fillable = [
        'empresa_id',
        'chave',
        'protocolo',
        'numero',
        'serie',
        'ambiente',
        'tp_emis',
        'status',
        'c_stat',
        'x_motivo',
        'xml_enviado',
        'xml_autorizado',
        'qr_code_url',
        'payload',
        'valor_total',
        'destinatario_doc',
        'destinatario_nome',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'valor_total' => 'decimal:2',
            'ambiente' => 'integer',
            'tp_emis' => 'integer',
            'numero' => 'integer',
            'serie' => 'integer',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function isAutorizada(): bool
    {
        return $this->status === 'autorizada';
    }
}
