<?php

namespace App\Services;

use App\Models\Cobranca;
use App\Models\Empresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AsaasService
{
    protected $baseUrl;
    protected $token;

    public function __construct(Empresa $empresa = null)
    {
        $this->baseUrl = config('services.asaas.url', 'https://sandbox.asaas.com/api/v3');
        
        // Se a empresa for passada, usa o token dela (Subconta). Se não, usa o da plataforma (Mestre)
        if ($empresa && $empresa->asaas_token) {
            $this->token = $empresa->asaas_token;
        } else {
            $this->token = config('services.asaas.token');
        }
    }

    /**
     * Cria uma cobrança no Asaas
     */
    public function criarCobranca(Cobranca $cobranca): array
    {
        // MOCK: Em produção, faria o POST /payments
        
        Log::info("AsaasService: Criando cobrança para {$cobranca->id} valor {$cobranca->valor}");

        return [
            'id' => 'pay_' . uniqid(), // ID do Asaas
            'invoiceUrl' => 'https://sandbox.asaas.com/i/mock_' . uniqid(),
            'bankSlipUrl' => 'https://sandbox.asaas.com/b/mock_' . uniqid(),
            'pixQrCode' => '00020126580014BR.GOV.BCB.PIX0136123e4567-e89b-12d3-a456-426614174000520400005303986540510.005802BR5913Cicrano de Tal6008BRASILIA62070503***6304E2CA',
            'pixVencimento' => now()->addDays(1)->format('Y-m-d')
        ];
    }

    /**
     * Cancela uma cobrança
     */
    public function cancelarCobranca(string $externalId): bool
    {
        // MOCK: Em produção, faria DELETE /payments/{id}
        Log::info("AsaasService: Cancelando cobrança {$externalId}");
        return true;
    }

    /**
     * Transfere valores para a conta bancária (Saque)
     */
    public function transferir(Empresa $empresa, float $valor): array
    {
        // MOCK: POST /transfers
        Log::info("AsaasService: Transferindo R$ {$valor} para {$empresa->razao_social}");

        return [
            'id' => 'transfer_' . uniqid(),
            'status' => 'PENDING',
            'dateCreated' => now()->format('Y-m-d')
        ];
    }

    /**
     * Cria uma subconta no Asaas para o Cliente/Empresa
     */
    public function criarSubconta(Empresa $empresa): array
    {
        // MOCK: POST /accounts
        Log::info("AsaasService: Criando subconta para {$empresa->cnpj}");

        return [
            'walletId' => 'wallet_' . uniqid(),
            'apiKey' => 'token_mock_' . \Illuminate\Support\Str::random(32)
        ];
    }
}
