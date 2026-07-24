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
        'inscricao_estadual',
        'crt',
        'nfce_serie',
        'nfce_ultimo_numero',
        'nfse_dps_ultimo_numero',
        'nfce_csc_id',
        'nfce_csc_token',
        'nfce_ambiente',
        'nfce_contingencia',
        'nfce_contingencia_motivo',
        'nfce_contingencia_desde',
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

    protected function casts(): array
    {
        return [
            'nfce_contingencia' => 'boolean',
            'nfce_contingencia_desde' => 'datetime',
            'crt' => 'integer',
            'nfce_serie' => 'integer',
            'nfce_ultimo_numero' => 'integer',
            'nfce_ambiente' => 'integer',
        ];
    }

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

    public function nfces()
    {
        return $this->hasMany(Nfce::class);
    }

    public function fornecedores()
    {
        return $this->hasMany(Fornecedor::class);
    }

    public function produtos()
    {
        return $this->hasMany(Produto::class);
    }

    public function documentosComerciais()
    {
        return $this->hasMany(DocumentoComercial::class);
    }

    public function modulos()
    {
        return $this->hasMany(EmpresaModulo::class);
    }

    public function formasPagamento()
    {
        return $this->hasMany(FormaPagamento::class);
    }

    public function temModulo(string $modulo): bool
    {
        $row = $this->modulos()->where('modulo', $modulo)->first();

        // Opt-in (ex.: contábil): só ativo com registro explícito
        if (in_array($modulo, EmpresaModulo::OPT_IN, true)) {
            return $row !== null && $row->ativo;
        }

        // Demais: sem registro = habilitado (opt-out)
        return $row === null || $row->ativo;
    }

    public function definirModulo(string $modulo, bool $ativo): EmpresaModulo
    {
        return $this->modulos()->updateOrCreate(
            ['modulo' => $modulo],
            ['ativo' => $ativo],
        );
    }
}
