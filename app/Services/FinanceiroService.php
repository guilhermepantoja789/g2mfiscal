<?php

namespace App\Services;

use App\Models\Cobranca;
use App\Models\NotaFiscal;
use Carbon\Carbon;

class FinanceiroService
{
    /**
     * Cria uma cobrança localmente baseada na Nota Fiscal
     */
    public function gerarCobrancaDeNota(NotaFiscal $nota, $dataVencimento)
    {
        // 1. Aqui entra a lógica de verificação
        // Ex: Se a empresa tem token do Asaas configurado, chamar API.
        // Por enquanto, vamos criar apenas o registro interno "OFFLINE".

        $cobranca = Cobranca::create([
            'empresa_id' => $nota->empresa_id,
            'cliente_id' => $nota->cliente_id,
            'nota_fiscal_id' => $nota->id,
            'valor' => $nota->valor_liquido ?? $nota->valor_servico, // Usa o líquido se houver retenção
            'vencimento' => $dataVencimento,
            'status' => 'PENDING',
            'descricao' => 'Referente à NFS-e (Rascunho) ' . $nota->id,
            'gateway' => 'manual', // Marcamos como manual por enquanto
        ]);

        return $cobranca;
    }

    /**
     * Atualiza a cobrança se a nota mudar (valor ou cliente)
     */
    public function atualizarCobrancaDaNota(NotaFiscal $nota, $dataVencimento)
    {
        $cobranca = Cobranca::where('nota_fiscal_id', $nota->id)->first();

        if ($cobranca && $cobranca->status === 'PENDING') {
            $cobranca->update([
                'cliente_id' => $nota->cliente_id, // Caso tenha mudado o tomador
                'valor' => $nota->valor_liquido ?? $nota->valor_servico,
                'vencimento' => $dataVencimento
            ]);
        }
    }
}
