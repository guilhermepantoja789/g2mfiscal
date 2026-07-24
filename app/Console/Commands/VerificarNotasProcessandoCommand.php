<?php

namespace App\Console\Commands;

use App\Models\NotaFiscal;
use App\Services\FinanceiroService;
use App\Services\NfseAmbiente;
use App\Services\NfseEmitPayloadBuilder;
use App\Services\NfseNacionalService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerificarNotasProcessandoCommand extends Command
{
    protected $signature = 'notas:verificar-processando';

    protected $description = 'Verifica notas presas no status processando há mais de 1 hora';

    public function handle(FinanceiroService $financeiroService)
    {
        $notas = NotaFiscal::where('status', 'processando')
            ->where('updated_at', '<', Carbon::now()->subHour())
            ->with(['empresa.certificado', 'cliente', 'servico', 'cobranca'])
            ->get();

        $this->info("Encontradas {$notas->count()} notas presas em processamento.");

        foreach ($notas as $nota) {
            $this->info("Analisando nota {$nota->id}...");

            // 1) Se já tem chave, consulta protocolo
            if ($nota->chave_acesso) {
                try {
                    $service = new NfseNacionalService($nota->empresa);
                    $retorno = $service->consultarNota($nota->chave_acesso);

                    if ($retorno['sucesso'] && isset($retorno['xml_autorizado'])) {
                        $this->marcarAutorizada($nota, $retorno, $financeiroService);
                        $this->info("Nota {$nota->id} atualizada para autorizada (consulta).");

                        continue;
                    }
                } catch (\Exception $e) {
                    Log::error("Erro ao consultar nota travada {$nota->id}: ".$e->getMessage());
                }
            }

            // 2) Se tem DPS enviada, retransmitir o mesmo XML (idempotência)
            if (filled($nota->xml_enviado)) {
                try {
                    $dados = NfseEmitPayloadBuilder::fromNota($nota);
                    $service = new NfseNacionalService($nota->empresa);
                    $retorno = $service->emitirNota($dados, $nota);

                    if ($retorno['sucesso']) {
                        $this->marcarAutorizada($nota, $retorno, $financeiroService);
                        $this->info("Nota {$nota->id} recuperada via retransmissão DPS.");

                        continue;
                    }

                    Log::warning("Retransmissão nota {$nota->id} sem sucesso: ".($retorno['mensagem'] ?? ''));
                } catch (\Exception $e) {
                    Log::error("Erro ao retransmitir nota travada {$nota->id}: ".$e->getMessage());
                }
            }

            $nota->update([
                'status' => 'erro',
                'mensagem_erro' => 'A nota demorou muito para processar e expirou o tempo limite local. Por favor, tente emitir novamente.',
            ]);
            $this->info("Nota {$nota->id} revertida para erro por timeout.");
        }

        $this->info('Finalizado.');
    }

    private function marcarAutorizada(NotaFiscal $nota, array $retorno, FinanceiroService $financeiroService): void
    {
        $nota->update([
            'status' => 'autorizada',
            'ambiente' => NfseAmbiente::label(),
            'numero_nfse' => $retorno['numero_nota'] ?? $nota->numero_nfse,
            'codigo_verificacao' => $retorno['codigo_verificacao'] ?? $nota->codigo_verificacao,
            'chave_acesso' => $retorno['chave_acesso'] ?? $nota->chave_acesso,
            'xml_autorizado' => $retorno['xml_autorizado'],
            'mensagem_erro' => null,
        ]);

        if ($nota->cobranca) {
            $financeiroService->ativarCobranca($nota->cobranca);
        }
    }
}
