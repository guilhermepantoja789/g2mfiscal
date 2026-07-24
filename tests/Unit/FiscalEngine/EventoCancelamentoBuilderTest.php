<?php

namespace Tests\Unit\FiscalEngine;

use App\Core\FiscalEngine\Xml\EventoCancelamentoBuilder;
use PHPUnit\Framework\TestCase;

class EventoCancelamentoBuilderTest extends TestCase
{
    public function test_build_cancelamento_110111(): void
    {
        $built = (new EventoCancelamentoBuilder)->build(
            chave: '13180612345678000195650110000000011123456781',
            cnpj: '12345678000195',
            nProt: '113250000000001',
            xJust: 'Cancelamento de teste homologacao',
            tpAmb: 2,
        );

        $this->assertStringStartsWith('ID110111', $built['id']);
        $this->assertStringEndsWith('01', $built['id']);
        $xml = $built['xml'];
        $this->assertStringContainsString('<tpEvento>110111</tpEvento>', $xml);
        $this->assertStringContainsString('<descEvento>Cancelamento</descEvento>', $xml);
        $this->assertStringContainsString('<nProt>113250000000001</nProt>', $xml);
        $this->assertStringContainsString('Id="'.$built['id'].'"', $xml);
    }

    public function test_justificativa_curta_falha(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new EventoCancelamentoBuilder)->build(
            chave: '13180612345678000195650110000000011123456781',
            cnpj: '12345678000195',
            nProt: '113250000000001',
            xJust: 'curto',
            tpAmb: 2,
        );
    }
}
