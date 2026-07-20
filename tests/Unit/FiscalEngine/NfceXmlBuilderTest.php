<?php

namespace Tests\Unit\FiscalEngine;

use App\Core\FiscalEngine\Dto\NfceEmitData;
use App\Core\FiscalEngine\Dto\NfceItem;
use App\Core\FiscalEngine\Dto\NfcePayment;
use App\Core\FiscalEngine\Xml\NfceXmlBuilder;
use PHPUnit\Framework\TestCase;

class NfceXmlBuilderTest extends TestCase
{
    public function test_builds_simples_nacional_xml_with_pag_and_troco(): void
    {
        $builder = new NfceXmlBuilder;
        $data = new NfceEmitData(
            cnpj: '12345678000195',
            razaoSocial: 'EMPRESA TESTE LTDA',
            nomeFantasia: 'EMPRESA TESTE',
            ie: '123456789',
            crt: 1,
            logradouro: 'RUA A',
            numero: '100',
            bairro: 'CENTRO',
            municipio: 'MANAUS',
            uf: 'AM',
            cep: '69000000',
            cMun: '1302603',
            fone: '92999999999',
            serie: 1,
            numeroNfce: 10,
            tpAmb: 2,
            destDoc: '12345678909',
            destNome: 'CONSUMIDOR',
            itens: [
                new NfceItem('PRODUTO', '22021000', '5102', 'UN', 1, 10.00, '102'),
            ],
            pagamentos: [
                new NfcePayment('01', 15.00, 5.00),
            ],
            cNF: '11223344',
            dhEmi: new \DateTimeImmutable('2025-07-19 10:00:00', new \DateTimeZone('America/Manaus')),
        );

        $result = $builder->build($data);
        $xml = $result['xml'];

        $this->assertSame(44, strlen($result['chave']));
        $this->assertStringContainsString('<mod>65</mod>', $xml);
        $this->assertStringContainsString('<CRT>1</CRT>', $xml);
        $this->assertStringContainsString('<CSOSN>102</CSOSN>', $xml);
        $this->assertStringContainsString('<CFOP>5102</CFOP>', $xml);
        $this->assertStringContainsString('<modFrete>9</modFrete>', $xml);
        $this->assertStringContainsString('<tPag>01</tPag>', $xml);
        $this->assertStringContainsString('<vTroco>5.00</vTroco>', $xml);
        $this->assertStringContainsString('<vPIS>0.00</vPIS>', $xml);
        $this->assertStringContainsString('Id="NFe'.$result['chave'].'"', $xml);
        $this->assertStringContainsString('<CPF>12345678909</CPF>', $xml);
    }

    public function test_omits_dest_when_not_identified(): void
    {
        $builder = new NfceXmlBuilder;
        $data = new NfceEmitData(
            cnpj: '12345678000195',
            razaoSocial: 'EMPRESA TESTE LTDA',
            nomeFantasia: 'EMPRESA TESTE',
            ie: '123456789',
            crt: 1,
            logradouro: 'RUA A',
            numero: '100',
            bairro: 'CENTRO',
            municipio: 'MANAUS',
            uf: 'AM',
            cep: '69000000',
            cMun: '1302603',
            fone: '',
            serie: 1,
            numeroNfce: 11,
            tpAmb: 2,
            itens: [new NfceItem('PRODUTO', '22021000', '5405', 'UN', 2, 5.50, '500')],
            pagamentos: [new NfcePayment('17', 11.00)],
            cNF: '99887766',
            dhEmi: new \DateTimeImmutable('2025-07-19 10:00:00', new \DateTimeZone('America/Manaus')),
        );

        $xml = $builder->build($data)['xml'];
        $this->assertStringNotContainsString('<dest>', $xml);
        $this->assertStringContainsString('<CSOSN>500</CSOSN>', $xml);
        $this->assertStringContainsString('<CFOP>5405</CFOP>', $xml);
        $this->assertStringContainsString('NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL', $xml);
        $this->assertStringNotContainsString('<vTroco>', $xml);
    }
}
