<?php

namespace Tests\Unit;

use App\Models\NotaFiscal;
use Tests\TestCase;

class NotaFiscalValoresTest extends TestCase
{
    public function test_liquido_igual_servico_sem_retencao(): void
    {
        $valores = NotaFiscal::calcularValores(1000.00, 5, 0, 1);

        $this->assertEquals(50.00, $valores['valor_iss']);
        $this->assertEquals(1000.00, $valores['valor_liquido']);
    }

    public function test_liquido_desconta_iss_quando_retido_pelo_tomador(): void
    {
        $valores = NotaFiscal::calcularValores(1000.00, 5, 0, 2);

        $this->assertEquals(50.00, $valores['valor_iss']);
        $this->assertEquals(950.00, $valores['valor_liquido']);
    }

    public function test_liquido_desconta_iss_quando_retido_pelo_intermediario(): void
    {
        $valores = NotaFiscal::calcularValores(200.00, 2, 0, 3);

        $this->assertEquals(4.00, $valores['valor_iss']);
        $this->assertEquals(196.00, $valores['valor_liquido']);
    }

    public function test_aliquota_cai_para_percentual_municipal_se_iss_zerado(): void
    {
        $valores = NotaFiscal::calcularValores(100.00, 0, 5, 2);

        $this->assertEquals(5.00, $valores['valor_iss']);
        $this->assertEquals(95.00, $valores['valor_liquido']);
    }

    public function test_tributos_lei_12741_nao_entram_no_liquido(): void
    {
        $nota = new NotaFiscal;
        $nota->valor_servico = 1000;
        $nota->aliquota_iss = 0;
        $nota->p_tot_trib_mun = 0;
        $nota->tp_ret_issqn = 1;
        $nota->v_tot_trib_fed = 133.50;
        $nota->v_tot_trib_mun = 20;

        $this->assertEquals(1000.00, $nota->calcularValorLiquido());
    }
}
