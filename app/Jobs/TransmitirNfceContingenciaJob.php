<?php

namespace App\Jobs;

use App\Contracts\FiscalIssuerInterface;
use App\Core\FiscalEngine\Exceptions\SefazRejectionException;
use App\Core\FiscalEngine\Exceptions\SefazTransportException;
use App\Models\DocumentoComercial;
use App\Models\Nfce;
use App\Services\Erp\DocumentoOrchestrator;
use App\Services\Fiscal\NfceEmitRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Transmite NFC-e emitida em contingência offline (tpEmis=9) quando a SEFAZ voltar.
 */
class TransmitirNfceContingenciaJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    /** @var list<int> */
    public array $backoff = [60, 120, 300, 600, 900];

    public function __construct(public Nfce $nfce) {}

    public function handle(FiscalIssuerInterface $issuer, DocumentoOrchestrator $orchestrator): void
    {
        $nfce = $this->nfce->fresh(['empresa.certificado', 'documentoComercial']);
        if (! $nfce || $nfce->status !== 'pendente_transmissao') {
            return;
        }

        if (! filled($nfce->xml_enviado) || ! filled($nfce->chave)) {
            $nfce->update(['status' => 'erro', 'x_motivo' => 'Contingência sem XML/chave para transmitir.']);

            return;
        }

        // Health-check: se SEFAZ fora, reagenda.
        try {
            $status = $issuer->statusServico($nfce->empresa);
            if (($status['cStat'] ?? '') !== '107') {
                $delay = $this->backoff[min($this->attempts() - 1, count($this->backoff) - 1)] ?? 900;
                $this->release($delay);

                return;
            }
        } catch (SefazTransportException) {
            $delay = $this->backoff[min($this->attempts() - 1, count($this->backoff) - 1)] ?? 900;
            $this->release($delay);

            return;
        }

        $nfce->update(['status' => 'processando']);

        // emit() com xml_enviado+chave → retransmitOrRecover
        $request = new NfceEmitRequest(
            itens: [],
            pagamentos: [],
            endpointProfile: $nfce->payload['endpoint_profile'] ?? null,
        );

        try {
            $issuer->emit($nfce->empresa, $request, $nfce);

            $nfce = $nfce->fresh();
            Log::info('NFC-e contingência transmitida', [
                'nfce_id' => $nfce->id,
                'chave' => $nfce->chave,
                'cStat' => $nfce->c_stat,
            ]);

            if ($nfce->documento_comercial_id && $nfce->status === 'autorizada') {
                $doc = DocumentoComercial::find($nfce->documento_comercial_id);
                if ($doc) {
                    $orchestrator->onFiscalAutorizado($doc);
                }
            }
        } catch (SefazRejectionException $e) {
            $nfce->update([
                'status' => 'rejeitado',
                'c_stat' => $e->cStat,
                'x_motivo' => $e->xMotivo,
            ]);

            if ($nfce->documento_comercial_id) {
                $doc = DocumentoComercial::find($nfce->documento_comercial_id);
                if ($doc) {
                    $orchestrator->onFiscalErro($doc, $e->xMotivo);
                }
            }
        } catch (SefazTransportException $e) {
            $nfce->update([
                'status' => 'pendente_transmissao',
                'x_motivo' => $e->getMessage(),
            ]);
            $delay = $this->backoff[min($this->attempts() - 1, count($this->backoff) - 1)] ?? 900;
            $this->release($delay);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->nfce->update([
            'status' => 'pendente_transmissao',
            'x_motivo' => $exception?->getMessage() ?? 'Falha na transmissão de contingência',
        ]);
    }
}
