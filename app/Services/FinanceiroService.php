<?php

namespace App\Services;

use App\Models\Cobranca;
use App\Models\NotaFiscal;
use Illuminate\Support\Facades\Log;
use App\Services\AsaasService;

class FinanceiroService
{
    protected $asaasService;

    public function __construct(AsaasService $asaasService)
    {
        $this->asaasService = $asaasService;
    }
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
            
            // Se já estiver PENDING (emitida), talvez devêssemos atualizar no Asaas também?
            // Fica como melhoria futura (PUT /payments/{id})
        }
    }

    /**
     * Ativa a cobrança no gateway (RASCUNHO -> PENDING)
     */
    public function ativarCobranca(Cobranca $cobranca)
    {
        if ($cobranca->status !== 'RASCUNHO') return;

        try {
            // 1. Cria no Asaas
            $dadosAsaas = $this->asaasService->criarCobranca($cobranca);

            // 2. Calcula Valor Líquido (Simulação de Taxa: R$ 1,99 + 0%)
            $valorLiquido = $this->calcularValorLiquido($cobranca->valor);

            // 3. Atualiza Local
            $cobranca->update([
                'status' => 'PENDING',
                'external_id' => $dadosAsaas['id'],
                'link_boleto' => $dadosAsaas['bankSlipUrl'] ?? null,
                'pix_qrcode' => $dadosAsaas['pixQrCode'] ?? null,
                'valor_liquido' => $valorLiquido
            ]);

            Log::info("Cobrança #{$cobranca->id} ativada no Asaas ID: {$dadosAsaas['id']}");

        } catch (\Exception $e) {
            Log::error("Erro ao ativar cobrança #{$cobranca->id}: " . $e->getMessage());
            // Não lançamos erro para não travar a emissão da nota, mas logamos
        }
    }

    /**
     * Calcula o valor líquido descontando taxas do gateway
     */
    public function calcularValorLiquido(float $valorBruto): float
    {
        // Exemplo: Taxa fixa de R$ 1,99 por boleto/pix
        $taxaFixa = 1.99;
        
        // Exemplo: Taxa percentual (ex: 1.99%)
        // $taxaPercentual = 0.0199;
        // $desconto = $valorBruto * $taxaPercentual;

        return max(0, $valorBruto - $taxaFixa);
    }
}
