<?php

namespace App\Jobs;

use App\Models\DocumentoComercial;
use App\Models\NotaFiscal;
use App\Notifications\EmissaoNotaConcluidaNotification;
use App\Notifications\EmissaoNotaFalhaNotification;
use App\Services\Erp\DocumentoOrchestrator;
use App\Services\FinanceiroService;
use App\Services\NfseAmbiente;
use App\Services\NfseEmitPayloadBuilder;
use App\Services\NfseNacionalService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class EmitirNotaFiscalJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [120, 300];

    public int $uniqueFor = 600;

    public function __construct(public NotaFiscal $nota) {}

    public function uniqueId(): string
    {
        return 'emitir-nfse-'.$this->nota->id;
    }

    public function handle(FinanceiroService $financeiroService, DocumentoOrchestrator $orchestrator): void
    {
        $nota = $this->nota->fresh(['empresa.certificado', 'empresa.dono', 'cliente', 'servico', 'cobranca', 'documentoComercial']);

        if (! $nota || ! in_array($nota->status, ['processando', 'criada', 'erro'], true)) {
            return;
        }

        try {
            $dados = NfseEmitPayloadBuilder::fromNota($nota);
        } catch (InvalidArgumentException $e) {
            $nota->update([
                'status' => 'erro',
                'mensagem_erro' => $e->getMessage(),
            ]);

            if ($nota->documento_comercial_id) {
                $doc = DocumentoComercial::find($nota->documento_comercial_id);
                if ($doc) {
                    $orchestrator->onFiscalErro($doc, $e->getMessage());
                }
            }

            if ($nota->empresa?->dono) {
                $nota->empresa->dono->notify(new EmissaoNotaFalhaNotification($nota, $e->getMessage()));
            }

            return;
        }

        try {
            $service = new NfseNacionalService($nota->empresa);
            $retorno = $service->emitirNota($dados, $nota);

            if (isset($retorno['xml_dps_enviado']) && blank($nota->xml_enviado)) {
                $nota->xml_enviado = $retorno['xml_dps_enviado'];
                $nota->save();
            }

            if ($retorno['sucesso']) {
                $nota->update([
                    'status' => 'autorizada',
                    'ambiente' => NfseAmbiente::label(),
                    'numero_nfse' => $retorno['numero_nota'],
                    'codigo_verificacao' => $retorno['codigo_verificacao'] ?? null,
                    'chave_acesso' => $retorno['chave_acesso'] ?? null,
                    'xml_autorizado' => $retorno['xml_autorizado'],
                    'mensagem_erro' => null,
                ]);

                if ($nota->cobranca) {
                    $financeiroService->ativarCobranca($nota->cobranca);
                    if ($nota->cobranca->descricao != 'Ref. NFS-e Nº '.$retorno['numero_nota']) {
                        $nota->cobranca->update(['descricao' => 'Ref. NFS-e Nº '.$retorno['numero_nota']]);
                    }
                }

                if ($nota->documento_comercial_id) {
                    $doc = DocumentoComercial::find($nota->documento_comercial_id);
                    if ($doc) {
                        $orchestrator->onFiscalAutorizado($doc);
                    }
                }

                if ($nota->empresa?->dono) {
                    $nota->empresa->dono->notify(new EmissaoNotaConcluidaNotification($nota));
                }

                return;
            }

            $msg = $retorno['mensagem'];
            $isBusinessError = false;

            if (isset($retorno['erros']) && is_array($retorno['erros'])) {
                $msgs = [];
                foreach ($retorno['erros'] as $e) {
                    $detalhe = is_array($e) ? ($e['Descricao'] ?? json_encode($e)) : $e;
                    $msgs[] = $detalhe;
                    $isBusinessError = true;
                }
                $msg = implode(' | ', $msgs);
            }

            if (! $isBusinessError) {
                throw new \Exception('Falha na API Nacional: '.$msg);
            }

            $nota->update([
                'status' => 'erro',
                'mensagem_erro' => $msg,
            ]);

            if ($nota->documento_comercial_id) {
                $doc = DocumentoComercial::find($nota->documento_comercial_id);
                if ($doc) {
                    $orchestrator->onFiscalErro($doc, $msg);
                }
            }

            if ($nota->empresa?->dono) {
                $nota->empresa->dono->notify(new EmissaoNotaFalhaNotification($nota, $msg));
            }
        } catch (\Exception $e) {
            Log::warning('EmitirNotaFiscalJob retryable', [
                'nota_id' => $nota->id,
                'attempt' => $this->attempts(),
                'erro' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $this->nota->update([
            'status' => 'erro',
            'mensagem_erro' => 'Erro interno ao processar nota: '.($exception?->getMessage() ?? 'desconhecido'),
        ]);

        $nota = $this->nota->fresh(['empresa.dono', 'documentoComercial']);
        if ($nota?->documento_comercial_id && $nota->documentoComercial) {
            app(DocumentoOrchestrator::class)->onFiscalErro(
                $nota->documentoComercial,
                $exception?->getMessage() ?? 'desconhecido'
            );
        }

        if ($nota?->empresa?->dono) {
            $nota->empresa->dono->notify(new EmissaoNotaFalhaNotification($nota, $exception?->getMessage() ?? 'desconhecido'));
        }
    }
}
