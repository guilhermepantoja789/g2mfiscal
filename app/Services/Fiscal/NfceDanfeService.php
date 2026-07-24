<?php

namespace App\Services\Fiscal;

use App\Models\Nfce;
use Illuminate\Support\Facades\Log;
use NFePHP\DA\NFe\Danfce;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class NfceDanfeService
{
    public function download(Nfce $nfce): Response
    {
        try {
            $pdf = $this->renderPdf($nfce);
        } catch (Throwable $e) {
            Log::error('Falha ao gerar DANFE NFC-e via Danfce', [
                'nfce_id' => $nfce->id,
                'erro' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                'Não foi possível gerar o DANFE a partir do XML da NFC-e: '.$e->getMessage(),
                previous: $e
            );
        }

        $filename = 'danfe-nfce-'.$nfce->numero.'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function renderPdf(Nfce $nfce): string
    {
        $xml = $this->resolveXml($nfce);

        $danfce = new Danfce($xml);
        $danfce->setPaperWidth(80);

        if ($nfce->isCancelada()) {
            $danfce->setAsCanceled();
        }

        $pdf = $danfce->render();

        if (! is_string($pdf) || ! str_starts_with($pdf, '%PDF')) {
            throw new RuntimeException('Danfce não retornou um PDF válido.');
        }

        return $pdf;
    }

    public function resolveXml(Nfce $nfce): string
    {
        $xml = filled($nfce->xml_autorizado)
            ? (string) $nfce->xml_autorizado
            : (string) ($nfce->xml_enviado ?? '');

        if ($xml === '') {
            throw new RuntimeException('NFC-e sem XML autorizado ou enviado para gerar o DANFE.');
        }

        return $xml;
    }
}
