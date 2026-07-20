<?php

namespace App\Services\Fiscal;

use App\Models\Nfce;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpFoundation\Response;

class NfceDanfeService
{
    public function download(Nfce $nfce): Response
    {
        $nfce->loadMissing('empresa');
        $qrDataUri = null;

        if ($nfce->qr_code_url) {
            $qrDataUri = $this->qrDataUri($nfce->qr_code_url);
        }

        $pdf = Pdf::loadView('pdf.danfe-nfce', [
            'nfce' => $nfce,
            'empresa' => $nfce->empresa,
            'qrDataUri' => $qrDataUri,
            'itens' => $nfce->payload['itens'] ?? [],
            'pagamentos' => $nfce->payload['pagamentos'] ?? [],
        ])->setPaper([0, 0, 226.77, 841.89]); // ~80mm width

        return $pdf->download('danfe-nfce-'.$nfce->numero.'.pdf');
    }

    public function qrDataUri(string $content): string
    {
        $builder = new Builder(
            writer: new PngWriter,
            data: $content,
            size: 200,
            margin: 0,
        );

        return $builder->build()->getDataUri();
    }
}
