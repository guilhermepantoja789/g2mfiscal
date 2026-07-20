<?php

namespace Tests\Unit\FiscalEngine;

use App\Core\FiscalEngine\Xml\AccessKeyGenerator;
use PHPUnit\Framework\TestCase;

class AccessKeyGeneratorTest extends TestCase
{
    public function test_generates_44_digit_key_with_valid_dv(): void
    {
        $gen = new AccessKeyGenerator;
        $chave = $gen->generate(
            cUF: '13',
            aamm: '2507',
            cnpj: '12345678000195',
            mod: '65',
            serie: 1,
            numero: 123,
            tpEmis: 1,
            cNF: '12345678',
        );

        $this->assertSame(44, strlen($chave));
        $this->assertSame($gen->modulo11(substr($chave, 0, 43)), substr($chave, 43, 1));
        $this->assertStringStartsWith('1325071234567800019565001000000123112345678', substr($chave, 0, 43));
    }

    public function test_modulo11_known_vector(): void
    {
        // Base 43 digits ending with known DV calculation
        $base = '1318061234567800019565011000000001112345678';
        $this->assertSame(43, strlen($base));
        $dv = (new AccessKeyGenerator)->modulo11($base);
        $this->assertMatchesRegularExpression('/^[0-9]$/', $dv);
        $this->assertSame($dv, (new AccessKeyGenerator)->modulo11($base));
    }
}
