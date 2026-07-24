<?php

namespace Tests\Unit;

use App\Services\NfseAmbiente;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class NfseAmbienteTest extends TestCase
{
    public function test_homolog_urls_when_not_production(): void
    {
        Config::set('app.env', 'local');
        Config::set('services.nfse_nacional.tp_amb', null);
        Config::set('services.nfse_nacional.url_sefin', null);
        Config::set('services.nfse_nacional.url_adn', null);
        Config::set('services.nfse_nacional.url_consulta_api', null);
        Config::set('services.nfse_nacional.urls', [
            'producao' => [
                'sefin' => 'https://sefin.nfse.gov.br/SefinNacional/nfse',
                'adn' => 'https://adn.nfse.gov.br',
                'consulta' => 'https://api.nfse.gov.br/nfse/v1/nfse',
            ],
            'homologacao' => [
                'sefin' => 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional/nfse',
                'adn' => 'https://adn.producaorestrita.nfse.gov.br',
                'consulta' => 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional/nfse',
            ],
        ]);

        $this->assertSame(2, NfseAmbiente::tpAmb());
        $this->assertSame('99', NfseAmbiente::serie());
        $this->assertSame('homologacao', NfseAmbiente::label());
        $this->assertStringContainsString('producaorestrita', NfseAmbiente::urlSefin());
        $this->assertStringContainsString('producaorestrita', NfseAmbiente::urlAdn());
    }

    public function test_producao_urls_when_tp_amb_override(): void
    {
        Config::set('app.env', 'local');
        Config::set('services.nfse_nacional.tp_amb', 1);
        Config::set('services.nfse_nacional.url_sefin', null);
        Config::set('services.nfse_nacional.url_adn', null);
        Config::set('services.nfse_nacional.urls.producao.sefin', 'https://sefin.nfse.gov.br/SefinNacional/nfse');
        Config::set('services.nfse_nacional.urls.producao.adn', 'https://adn.nfse.gov.br');
        Config::set('services.nfse_nacional.urls.producao.consulta', 'https://api.nfse.gov.br/nfse/v1/nfse');

        $this->assertSame(1, NfseAmbiente::tpAmb());
        $this->assertSame('1', NfseAmbiente::serie());
        $this->assertStringContainsString('sefin.nfse.gov.br', NfseAmbiente::urlSefin());
        $this->assertStringNotContainsString('producaorestrita', NfseAmbiente::urlSefin());
    }

    public function test_rejects_mismatch_producao_with_restrita_url(): void
    {
        Config::set('services.nfse_nacional.tp_amb', 1);
        Config::set('services.nfse_nacional.url_sefin', 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional/nfse');

        $this->expectException(\InvalidArgumentException::class);
        NfseAmbiente::urlSefin();
    }

    public function test_rejects_mismatch_homolog_with_prod_url(): void
    {
        Config::set('services.nfse_nacional.tp_amb', 2);
        Config::set('services.nfse_nacional.url_sefin', 'https://sefin.nfse.gov.br/SefinNacional/nfse');

        $this->expectException(\InvalidArgumentException::class);
        NfseAmbiente::urlSefin();
    }
}
