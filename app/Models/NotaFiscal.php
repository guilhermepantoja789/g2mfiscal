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
        'servico_id',
        'documento_comercial_id',
        'status',
        'ambiente',
        'numero_nfse',
        'numero_dps',
        'codigo_verificacao',
        'chave_acesso',
        'link_pdf',
        'mensagem_erro',

        // Dados Tomador
        'tomador_cnpj',
        'tomador_nome',
        'tomador_email',

        // Valores e Serviço
        'valor_servico',
        'valor_iss',
        'valor_liquido',
        'descricao',
        'emissao',

        // Fiscal
        'trib_issqn',
        'tp_ret_issqn',
        'fin_nfse',
        'ind_final',
        'ind_dest',
        'c_ind_op',
        'cst_ibscbs',
        'c_class_trib',
        'aliquota_iss',

        // Tributos Aprox
        'p_tot_trib_fed', 'v_tot_trib_fed',
        'p_tot_trib_est', 'v_tot_trib_est',
        'p_tot_trib_mun', 'v_tot_trib_mun',

        'xml_autorizado',
        'xml_enviado'
    ];

    protected $casts = [
        'emissao' => 'datetime',
        'valor_servico' => 'decimal:2',
        'valor_iss' => 'decimal:2',
        'valor_liquido' => 'decimal:2',
        'aliquota_iss' => 'decimal:2',
        'v_tot_trib_fed' => 'decimal:2',
        'v_tot_trib_est' => 'decimal:2',
        'v_tot_trib_mun' => 'decimal:2',
        'p_tot_trib_mun' => 'decimal:2',
    ];

    /* -------------------------------------------------------------------------
     * RELACIONAMENTOS
     * ------------------------------------------------------------------------- */

    public function empresa() { return $this->belongsTo(Empresa::class); }
    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function servico() { return $this->belongsTo(Servico::class); }
    public function documentoComercial() { return $this->belongsTo(DocumentoComercial::class); }

    /* -------------------------------------------------------------------------
     * ACCESSORS (Define o que aparece na View)
     * ------------------------------------------------------------------------- */

    // Retorna o Texto do Status (status_label)
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'autorizada' => 'Autorizada',
            'processando' => 'Processando',
            'erro' => 'Erro / Rejeitada',
            'cancelada' => 'Cancelada',
            'rascunho' => 'Rascunho',
            default => ucfirst($this->status),
        };
    }

    // Retorna a Cor do Status (status_color)
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'autorizada' => 'bg-green-100 text-green-800',
            'processando' => 'bg-yellow-100 text-yellow-800',
            'erro' => 'bg-red-100 text-red-800',
            'cancelada' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800', // Rascunho
        };
    }

    public function cobranca()
    {
        return $this->hasOne(Cobranca::class, 'nota_fiscal_id');
    }

    public function issRetido(): bool
    {
        return in_array((int) $this->tp_ret_issqn, [2, 3], true);
    }

    public function aliquotaIssEfetiva(): float
    {
        $aliq = (float) $this->aliquota_iss;
        if ($aliq > 0) {
            return $aliq;
        }

        return (float) ($this->p_tot_trib_mun ?? 0);
    }

    public function calcularValorIss(): float
    {
        return round((float) $this->valor_servico * $this->aliquotaIssEfetiva() / 100, 2);
    }

    public function calcularValorLiquido(): float
    {
        $servico = (float) $this->valor_servico;

        return $this->issRetido()
            ? round($servico - $this->calcularValorIss(), 2)
            : $servico;
    }

    /**
     * @return array{valor_iss: float, valor_liquido: float}
     */
    public function valoresCalculados(): array
    {
        return [
            'valor_iss' => $this->calcularValorIss(),
            'valor_liquido' => $this->calcularValorLiquido(),
        ];
    }

    /**
     * @return array{valor_iss: float, valor_liquido: float}
     */
    public static function calcularValores(
        float $valorServico,
        mixed $aliquotaIss,
        mixed $pTotTribMun,
        mixed $tpRetIssqn,
    ): array {
        $nota = new self;
        $nota->valor_servico = $valorServico;
        $nota->aliquota_iss = $aliquotaIss;
        $nota->p_tot_trib_mun = $pTotTribMun;
        $nota->tp_ret_issqn = $tpRetIssqn;

        return $nota->valoresCalculados();
    }
}
