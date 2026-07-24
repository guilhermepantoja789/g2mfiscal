<?php

namespace Tests\Unit\FiscalEngine;

use App\Core\FiscalEngine\Exceptions\SefazRejectionException;
use App\Core\FiscalEngine\Exceptions\SefazTransportException;
use App\Core\FiscalEngine\Transport\SefazEndpoints;
use App\Core\FiscalEngine\Transport\SefazSoapClient;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SefazSoapClientParseTest extends TestCase
{
    public function test_parse_autorizado_builds_nfeProc(): void
    {
        $client = new SefazSoapClient(new SefazEndpoints([]));
        $method = (new ReflectionClass($client))->getMethod('parseAutorizacaoResponse');
        $method->setAccessible(true);

        $nfe = '<NFe xmlns="http://www.portalfiscal.inf.br/nfe"><infNFe Id="NFe123"/></NFe>';
        $response = '<?xml version="1.0"?>
            <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
              <soap:Body>
                <nfeResultMsg>
                  <retEnviNFe>
                    <cStat>104</cStat>
                    <xMotivo>Lote processado</xMotivo>
                    <protNFe>
                      <infProt>
                        <cStat>100</cStat>
                        <xMotivo>Autorizado o uso da NF-e</xMotivo>
                        <nProt>113250000000001</nProt>
                      </infProt>
                    </protNFe>
                  </retEnviNFe>
                </nfeResultMsg>
              </soap:Body>
            </soap:Envelope>';

        $result = $method->invoke($client, $response, $nfe);

        $this->assertTrue($result['autorizado']);
        $this->assertSame('100', $result['cStat']);
        $this->assertSame('113250000000001', $result['protocolo']);
        $this->assertStringContainsString('<nfeProc', $result['nfeProc']);
        $this->assertStringContainsString($nfe, $result['nfeProc']);
        $this->assertStringContainsString('<protNFe>', $result['nfeProc']);
    }

    public function test_parse_rejeicao_throws_non_retryable(): void
    {
        $client = new SefazSoapClient(new SefazEndpoints([]));
        $method = (new ReflectionClass($client))->getMethod('parseAutorizacaoResponse');
        $method->setAccessible(true);

        $response = '<retEnviNFe><cStat>215</cStat><xMotivo>Falha no schema XML</xMotivo></retEnviNFe>';

        try {
            $method->invoke($client, $response, '<NFe/>');
            $this->fail('Esperava SefazRejectionException');
        } catch (SefazRejectionException $e) {
            $this->assertSame('215', $e->cStat);
            $this->assertFalse($e->isRetryable());
            $this->assertStringContainsString('schema', $e->xMotivo);
        }
    }

    public function test_transport_exception_is_retryable(): void
    {
        $e = new SefazTransportException('timeout');
        $this->assertTrue($e->isRetryable());
    }

    public function test_parse_evento_135(): void
    {
        $client = new SefazSoapClient(new SefazEndpoints([]));
        $method = (new ReflectionClass($client))->getMethod('parseEventoResponse');
        $method->setAccessible(true);

        $response = '<retEnvEvento><retEvento><infEvento>
            <cStat>135</cStat>
            <xMotivo>Evento registrado e vinculado a NF-e</xMotivo>
            <nProt>113250000000099</nProt>
        </infEvento></retEvento></retEnvEvento>';

        $result = $method->invoke($client, $response);
        $this->assertSame('135', $result['cStat']);
        $this->assertSame('113250000000099', $result['protocolo']);
    }

    public function test_parse_inutilizacao_102(): void
    {
        $client = new SefazSoapClient(new SefazEndpoints([]));
        $method = (new ReflectionClass($client))->getMethod('parseInutilizacaoResponse');
        $method->setAccessible(true);

        $response = '<retInutNFe><infInut>
            <cStat>102</cStat>
            <xMotivo>Inutilizacao de numero homologado</xMotivo>
            <nProt>113250000000088</nProt>
        </infInut></retInutNFe>';

        $result = $method->invoke($client, $response);
        $this->assertSame('102', $result['cStat']);
        $this->assertSame('113250000000088', $result['protocolo']);
    }
}
