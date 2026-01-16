<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empresa extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'cnpj',
        'razao_social',
        'nome_fantasia',
        'inscricao_municipal',
        'regime_tributario',        // 1:Não Optante, 2:MEI, 3:Simples
        'regime_apuracao_sn',       // 1:Pelo SN (Se regime=3)
        'regime_especial_tributacao', // 0:Nenhum
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'uf',
        'cod_ibge_mun',
        'email',
        'telefone',

        // --- NOVOS CAMPOS FINANCEIROS (MÓDULO ASAAS) ---
        'asaas_wallet_id',     // ID da subconta
        'asaas_token',         // Token da API da subconta

        // Dados Bancários para Saque (Destino do dinheiro)
        'banco_codigo',
        'banco_nome',
        'agencia',
        'conta',
        'conta_tipo',          // CC ou CP
        'chave_pix',

        // Configurações de Automação
        'saque_automatico',
        'saque_frequencia_dias'
    ];

    // Constantes para ajudar no código
    const REGIME_NAO_OPTANTE = 1;
    const REGIME_MEI = 2;
    const REGIME_SIMPLES = 3;

    // =========================================================================
    // RELACIONAMENTOS
    // =========================================================================

    /**
     * Relacionamento com Usuários (Equipe)
     * Tabela Pivot: empresa_user
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'empresa_user')
            ->withPivot('perfil')
            ->withTimestamps();
    }

    /**
     * O Dono/Criador original do registro
     */
    public function dono()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Certificado Digital (Último ativo)
     */
    public function certificado()
    {
        return $this->hasOne(Certificado::class)->where('ativo', true)->latest();
    }

    /**
     * Catálogo de Serviços
     */
    public function servicos()
    {
        return $this->hasMany(Servico::class);
    }

    /**
     * Clientes (Tomadores)
     */
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }
}
