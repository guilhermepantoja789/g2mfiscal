<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transferencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'valor', 'taxa', 'status',
        'banco_destino', 'agencia_destino', 'conta_destino', 'chave_pix_destino',
        'external_id', 'data_solicitacao', 'data_liquidacao'
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'taxa' => 'decimal:2',
        'data_solicitacao' => 'datetime',
        'data_liquidacao' => 'datetime',
    ];

    // Status visual
    public function getStatusLabelAttribute() {
        return match($this->status) {
            'PROCESSING' => 'Processando',
            'DONE' => 'Transferido',
            'FAILED' => 'Falhou',
            default => $this->status
        };
    }

    public function getStatusColorAttribute() {
        return match($this->status) {
            'PROCESSING' => 'text-yellow-600 bg-yellow-100',
            'DONE' => 'text-green-600 bg-green-100',
            'FAILED' => 'text-red-600 bg-red-100',
            default => 'text-gray-600 bg-gray-100'
        };
    }
}
