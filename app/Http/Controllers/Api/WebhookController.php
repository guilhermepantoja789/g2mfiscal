<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cobranca;
use App\Models\Transferencia;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Recebe notificações do Asaas
     */
    public function handleAsaas(Request $request)
    {
        // 1. Log do Payload (Importante para debug)
        Log::info('Webhook Asaas recebido', $request->all());

        $event = $request->input('event');
        $payment = $request->input('payment');
        
        // Validação básica
        if (!$event || !$payment) {
            return response()->json(['status' => 'ignored'], 200);
        }

        try {
            // 2. Processa Pagamento Recebido
            if ($event === 'PAYMENT_RECEIVED') {
                $this->processarPagamento($payment);
            }
            
            // 3. Processa Transferência (Saque)
            if (in_array($event, ['TRANSFER_DONE', 'TRANSFER_FAILED'])) {
                $this->processarTransferencia($request->input('transfer'), $event);
            }

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error("Erro no webhook Asaas: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function processarPagamento(array $paymentData)
    {
        $externalId = $paymentData['id'];
        
        // Busca a cobrança pelo ID externo (se já integrado)
        // Como ainda estamos no mock/híbrido, podemos tentar buscar pelo nosso ID se salvarmos no `external_id`
        $cobranca = Cobranca::where('external_id', $externalId)->first();

        if ($cobranca) {
            $cobranca->update([
                'status' => 'RECEIVED',
                'valor_liquido' => $paymentData['netValue'] ?? $cobranca->valor, // Valor já descontado taxas
                'updated_at' => now()
            ]);
            Log::info("Cobrança #{$cobranca->id} baixada via Webhook.");
        } else {
            Log::warning("Webhook pagamento recebido para ID desconhecido: {$externalId}");
        }
    }

    private function processarTransferencia($transferData, $event)
    {
        if (!$transferData) return;

        $externalId = $transferData['id'];
        $transferencia = Transferencia::where('external_id', $externalId)->first();

        if ($transferencia) {
            $novoStatus = ($event === 'TRANSFER_DONE') ? 'DONE' : 'FAILED';
            $transferencia->update([
                'status' => $novoStatus,
                'data_liquidacao' => now()
            ]);
            Log::info("Transferência #{$transferencia->id} atualizada para {$novoStatus}.");
        }
    }
}
