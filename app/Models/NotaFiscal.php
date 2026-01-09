<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaFiscal extends Model
{
    use HasFactory;

    protected $table = 'nota_fiscais';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'status',
        'numero_nfse',
        'codigo_verificacao',
        'link_pdf',
        'mensagem_erro',
        'tomador_cnpj',
        'tomador_nome',
        'tomador_email',
        'valor_servico',
        'aliquota_iss',
        'valor_iss',
        'valor_liquido',
        'descricao',
        'codigo_servico',
        'ambiente',
        'xml_autorizado',
        'xml_enviado'
    ];

    // --- ADICIONE ESTES DOIS MÉTODOS ABAIXO ---

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    // ------------------------------------------

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'autorizada' => 'bg-green-100 text-green-800',
            'processando' => 'bg-yellow-100 text-yellow-800',
            'erro' => 'bg-red-100 text-red-800',
            'cancelada' => 'bg-gray-100 text-gray-800',
            default => 'bg-blue-50 text-blue-800',
        };
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst($this->status);
    }
}
