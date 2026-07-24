<?php

namespace App\Jobs;

use App\Contracts\FiscalIssuerInterface;
use App\Core\FiscalEngine\Dto\NfceItem;
use App\Core\FiscalEngine\Dto\NfcePayment;
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

class EmitirNfceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 60, 120, 300];

    public function __construct(public Nfce $nfce) {}

    public function handle(FiscalIssuerInterface $issuer, DocumentoOrchestrator $orchestrator): void
    {
        $nfce = $this->nfce->fresh(['empresa.certificado', 'documentoComercial']);
        if (! $nfce || ! in_array($nfce->status, ['processando', 'criada', 'erro'], true)) {
            return;
        }

        $payload = $nfce->payload ?? [];
        $itens = array_map(
            fn (array $i) => new NfceItem(
                descricao: $i['descricao'],
                ncm: $i['ncm'],
                cfop: $i['cfop'],
                unidade: $i['unidade'] ?? 'UN',
                quantidade: (float) $i['quantidade'],
                valorUnitario: (float) $i['valor_unitario'],
                csosn: $i['csosn'] ?? '102',
                pisCst: $i['pis_cst'] ?? '49',
                cofinsCst: $i['cofins_cst'] ?? '49',
                cEAN: $i['cean'] ?? null,
                cProd: $i['cprod'] ?? null,
            ),
            $payload['itens'] ?? [],
        );

        $pagamentos = array_map(
            fn (array $p) => new NfcePayment(
                tPag: $p['t_pag'],
                vPag: (float) $p['v_pag'],
                vTroco: isset($p['v_troco']) ? (float) $p['v_troco'] : null,
            ),
            $payload['pagamentos'] ?? [],
        );

        $request = new NfceEmitRequest(
            itens: $itens,
            pagamentos: $pagamentos,
            destDoc: $payload['dest_doc'] ?? null,
            destNome: $payload['dest_nome'] ?? null,
            naturezaOperacao: $payload['natureza'] ?? 'VENDA',
            numeroOverride: $nfce->numero > 0 ? $nfce->numero : null,
            endpointProfile: $payload['endpoint_profile'] ?? null,
            tpEmis: (int) ($nfce->tp_emis ?: 1),
            xJustContingencia: $payload['x_just_contingencia']
                ?? $nfce->empresa?->nfce_contingencia_motivo,
        );

        $nfce->update(['status' => 'processando']);

        try {
            $issuer->emit($nfce->empresa, $request, $nfce);

            $nfce = $nfce->fresh();
            Log::info('NFC-e autorizada', [
                'nfce_id' => $nfce->id,
                'chave' => $nfce->chave,
                'cStat' => $nfce->c_stat,
                'status' => $nfce->status,
            ]);

            if ($nfce->status === 'pendente_transmissao') {
                TransmitirNfceContingenciaJob::dispatch($nfce);

                return;
            }

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

            Log::warning('NFC-e rejeitada pela SEFAZ', [
                'nfce_id' => $nfce->id,
                'cStat' => $e->cStat,
                'xMotivo' => $e->xMotivo,
            ]);

            // Não reentra na fila
            return;
        } catch (SefazTransportException $e) {
            $nfce->update([
                'status' => 'erro',
                'x_motivo' => $e->getMessage(),
            ]);

            Log::warning('NFC-e falha de transporte', [
                'nfce_id' => $nfce->id,
                'erro' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            $delay = $this->backoff[min($this->attempts() - 1, count($this->backoff) - 1)] ?? 300;
            $this->release($delay);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->nfce->update([
            'status' => 'erro',
            'x_motivo' => $exception?->getMessage() ?? 'Falha desconhecida na emissão NFC-e',
        ]);

        $nfce = $this->nfce->fresh();
        if ($nfce?->documento_comercial_id) {
            $doc = DocumentoComercial::find($nfce->documento_comercial_id);
            if ($doc) {
                app(DocumentoOrchestrator::class)->onFiscalErro(
                    $doc,
                    $exception?->getMessage() ?? 'Falha desconhecida na emissão NFC-e'
                );
            }
        }
    }
}
