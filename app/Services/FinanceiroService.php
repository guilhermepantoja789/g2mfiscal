<?php

namespace App\Services;

use App\Models\Cobranca;
use App\Models\NotaFiscal;
use Illuminate\Support\Facades\Log;

class FinanceiroService
{
    /**
     * Cria uma cobrança vinculada a uma Nota Fiscal (Estado Inicial: RASCUNHO)
     */
    public function gerarCobrancaDeNota(NotaFiscal $nota, $vencimento)
    {
        try {
            // Lógica interna para criar o registro "OFFLINE" inicialmente
            // Se futuramente integrar com API (Asaas/Iugu), a chamada seria feita na emissão da nota, não aqui.

            return Cobranca::create([
                'empresa_id'     => $nota->empresa_id,
                'cliente_id'     => $nota->cliente_id,
                'nota_fiscal_id' => $nota->id,

                'valor'          => $nota->valor_liquido ?? $nota->valor_servico, // Usa o líquido se houver retenção

                'vencimento'     => $vencimento,
                'status'         => 'RASCUNHO', // <--- CORREÇÃO: Nasce inativa (cinza)

                'descricao'      => 'Pré-lançamento ref. NFS-e (Rascunho #' . $nota->id . ')',
                'gateway'        => 'manual'
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao gerar cobrança da nota {$nota->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Atualiza a cobrança existente quando o usuário edita a Nota
     */
    public function atualizarCobrancaDaNota(NotaFiscal $nota, $vencimento)
    {
        $cobranca = Cobranca::where('nota_fiscal_id', $nota->id)->first();

        // Se não existir cobrança, cria uma nova
        if (!$cobranca) {
            return $this->gerarCobrancaDeNota($nota, $vencimento);
        }

        // CORREÇÃO: Permite atualizar se for RASCUNHO ou PENDING (Aguardando)
        // Bloqueia apenas se já estiver Paga (RECEIVED) ou Cancelada
        if (in_array($cobranca->status, ['RASCUNHO', 'PENDING'])) {

            $cobranca->update([
                'cliente_id'    => $nota->cliente_id, // Caso tenha mudado o cliente
                'valor'         => $nota->valor_liquido ?? $nota->valor_servico,
                'vencimento'    => $vencimento,
                'descricao'     => 'Pré-lançamento ref. NFS-e (Rascunho #' . $nota->id . ')'
            ]);
        }
    }
}
