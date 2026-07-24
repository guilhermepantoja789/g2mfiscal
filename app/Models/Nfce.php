<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nfce extends Model
{
    protected $table = 'nfces';

    protected $fillable = [
        'empresa_id',
        'documento_comercial_id',
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
        'data_emissao',
        'destinatario_doc',
        'destinatario_nome',
        'cancelado_em',
        'protocolo_cancelamento',
        'motivo_cancelamento',
        'xml_evento_cancelamento',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'valor_total' => 'decimal:2',
            'data_emissao' => 'date',
            'ambiente' => 'integer',
            'tp_emis' => 'integer',
            'numero' => 'integer',
            'serie' => 'integer',
            'cancelado_em' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function documentoComercial(): BelongsTo
    {
        return $this->belongsTo(DocumentoComercial::class);
    }

    public function isAutorizada(): bool
    {
        return $this->status === 'autorizada';
    }

    public function isCancelada(): bool
    {
        return $this->status === 'cancelada';
    }

    public function isPendenteTransmissao(): bool
    {
        return $this->status === 'pendente_transmissao';
    }

    public function podeImprimirDanfe(): bool
    {
        return in_array($this->status, ['autorizada', 'pendente_transmissao', 'cancelada'], true)
            && (filled($this->xml_autorizado) || filled($this->xml_enviado));
    }

    public function podeCancelar(): bool
    {
        return $this->status === 'autorizada'
            && filled($this->chave)
            && filled($this->protocolo);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'autorizada' => 'Autorizada',
            'pendente_transmissao' => 'Pendente transmissão',
            'processando' => 'Processando',
            'cancelada' => 'Cancelada',
            'erro', 'rejeitada' => 'Erro / Rejeitada',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'autorizada' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 shadow-sm',
            'pendente_transmissao', 'processando' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 shadow-sm animate-pulse',
            'erro', 'rejeitada' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 shadow-sm',
            'cancelada' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 shadow-sm',
            default => 'bg-slate-50 text-slate-600 ring-1 ring-slate-500/20',
        };
    }
}
