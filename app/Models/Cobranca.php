<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cobranca extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'nota_fiscal_id',
        'gateway',
        'external_id',
        'valor',
        'valor_liquido',
        'vencimento',
        'status',
        'link_boleto',
        'pix_qrcode',
        'pix_imagem',
        'descricao'
    ];

    protected $casts = [
        'vencimento' => 'date',
        'valor' => 'decimal:2',
        'valor_liquido' => 'decimal:2',
    ];

    // Status visual (Badge)
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'RECEIVED' => 'bg-green-100 text-green-800', // Pago
            'PENDING' => 'bg-yellow-100 text-yellow-800', // Aguardando
            'OVERDUE' => 'bg-red-100 text-red-800', // Vencido
            'CANCELLED' => 'bg-gray-100 text-gray-800', // Cancelado
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'RECEIVED' => 'Pago',
            'PENDING' => 'Aguardando',
            'OVERDUE' => 'Vencido',
            'CANCELLED' => 'Cancelado',
            default => $this->status,
        };
    }

    public function notaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
