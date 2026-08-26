<?php

namespace Tests\Unit;

use Tests\TestCase;

class NfseCorrigirProducaoCommandTest extends TestCase
{
    public function test_comando_force_roda_nas_duas_etapas(): void
    {
        $this->artisan('nfse:corrigir-producao', ['--force' => true])
            ->expectsOutputToContain('1/2 nDPS')
            ->expectsOutputToContain('2/2 valores')
            ->assertSuccessful();
    }

    public function test_dry_run_nao_pede_confirmacao(): void
    {
        $this->artisan('nfse:corrigir-producao', ['--dry-run' => true])
            ->assertSuccessful();
    }
}
