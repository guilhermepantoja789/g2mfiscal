<?php

namespace Tests\Unit\Fiscal;

use App\Models\Nfce;
use App\Services\Fiscal\NfceDanfeService;
use RuntimeException;
use Tests\TestCase;

class NfceDanfeServiceTest extends TestCase
{
    public function test_render_pdf_from_xml_autorizado(): void
    {
        $nfce = new Nfce([
            'numero' => 1,
            'serie' => 1,
            'status' => 'autorizada',
            'xml_autorizado' => $this->fixture('nfce-autorizada-minima.xml'),
        ]);

        $pdf = (new NfceDanfeService)->renderPdf($nfce);

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_render_pdf_from_xml_enviado_quando_contingencia(): void
    {
        $nfce = new Nfce([
            'numero' => 2,
            'serie' => 1,
            'status' => 'pendente_transmissao',
            'tp_emis' => 9,
            'xml_enviado' => $this->fixture('nfce-contingencia-minima.xml'),
        ]);

        $pdf = (new NfceDanfeService)->renderPdf($nfce);

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_render_pdf_marca_cancelada(): void
    {
        $nfce = new Nfce([
            'numero' => 3,
            'serie' => 1,
            'status' => 'cancelada',
            'xml_autorizado' => $this->fixture('nfce-autorizada-minima.xml'),
        ]);

        $pdf = (new NfceDanfeService)->renderPdf($nfce);

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_prefer_xml_autorizado_sobre_enviado(): void
    {
        $nfce = new Nfce([
            'numero' => 4,
            'status' => 'autorizada',
            'xml_autorizado' => $this->fixture('nfce-autorizada-minima.xml'),
            'xml_enviado' => '<NFe/>',
        ]);

        $resolved = (new NfceDanfeService)->resolveXml($nfce);

        $this->assertStringContainsString('<nfeProc', $resolved);
    }

    public function test_falha_sem_xml(): void
    {
        $this->expectException(RuntimeException::class);

        (new NfceDanfeService)->renderPdf(new Nfce([
            'numero' => 5,
            'status' => 'autorizada',
        ]));
    }

    private function fixture(string $name): string
    {
        return (string) file_get_contents(base_path('tests/Fixtures/Nfce/'.$name));
    }
}
