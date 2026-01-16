<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recorrencia extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'servico_id',
        'descricao_recorrencia',
        'frequencia',
        'proxima_execucao',
        'data_fim',
        'ativo',
        'emitir_automaticamente',

        // Dados da Nota (Tomador) - ADICIONADOS OS NOVOS
        'tomador_cnpj',
        'tomador_nome',
        'tomador_email',
        'tomador_telefone',
        'tomador_im',
        'tomador_cep',
        'tomador_endereco',
        'tomador_numero',
        'tomador_complemento',
        'tomador_bairro',
        'tomador_cidade',
        'tomador_uf',

        // Valores e Serviço
        'valor_servico',
        'descricao_servico',

        // Fiscal
        'trib_issqn',
        'tp_ret_issqn',

        // Impostos
        'p_tot_trib_fed',
        'p_tot_trib_est',
        'p_tot_trib_mun',
    ];

    protected $casts = [
        'proxima_execucao' => 'date',
        'data_fim' => 'date',
        'ativo' => 'boolean',
        'emitir_automaticamente' => 'boolean',
        'valor_servico' => 'decimal:2',
        'p_tot_trib_fed' => 'decimal:2',
        'p_tot_trib_est' => 'decimal:2',
        'p_tot_trib_mun' => 'decimal:2',
    ];

    // ... Relacionamentos (mantém igual) ...
    public function empresa() { return $this->belongsTo(Empresa::class); }
    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function servico() { return $this->belongsTo(Servico::class); }

    public function getStatusLabelAttribute()
    {
        if (!$this->ativo) return 'Pausada';
        if ($this->data_fim && $this->data_fim < now()) return 'Finalizada';
        return 'Ativa';
    }
}
