<?php

namespace Tests\Unit\FiscalEngine;

use App\Core\FiscalEngine\Transport\SefazEndpoints;
use PHPUnit\Framework\TestCase;

class SefazEndpointsProfileTest extends TestCase
{
    private function endpoints(): SefazEndpoints
    {
        return new SefazEndpoints([
            'endpoint_profile' => 'homolog_nac',
            'urls' => [
                'producao' => ['autorizacao' => 'https://example.test/prod'],
                'homolog_nac' => ['autorizacao' => 'https://example.test/nac'],
                'homolog' => ['autorizacao' => 'https://example.test/hom'],
            ],
        ]);
    }

    public function test_producao_ambiente_forces_producao_profile(): void
    {
        $endpoints = $this->endpoints();

        $this->assertSame('producao', $endpoints->profileForAmbiente(1, 'homolog_nac'));
        $this->assertSame('producao', $endpoints->profileForAmbiente(1, 'producao'));
    }

    public function test_homolog_ambiente_rejects_producao_profile(): void
    {
        $endpoints = $this->endpoints();

        $this->assertSame('homolog', $endpoints->profileForAmbiente(2, 'homolog'));
        $this->assertSame('homolog_nac', $endpoints->profileForAmbiente(2, 'homolog_nac'));
        $this->assertSame('homolog_nac', $endpoints->profileForAmbiente(2, 'producao'));
        $this->assertSame('homolog_nac', $endpoints->profileForAmbiente(2, null));
    }
}
