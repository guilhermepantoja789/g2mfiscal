<?php

namespace Tests\Unit;

use App\Contracts\FiscalIssuerInterface;
use App\Core\FiscalEngine\Exceptions\SefazRejectionException;
use App\Core\FiscalEngine\Exceptions\SefazTransportException;
use App\Jobs\EmitirNfceJob;
use App\Models\Empresa;
use App\Models\Nfce;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EmitirNfceJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejection_marks_rejeitado_without_throwing(): void
    {
        $empresa = $this->makeEmpresa();
        $nfce = $this->makeNfce($empresa, 1);

        $issuer = Mockery::mock(FiscalIssuerInterface::class);
        $issuer->shouldReceive('emit')
            ->once()
            ->andThrow(new SefazRejectionException('215', 'Rejeicao schema'));

        (new EmitirNfceJob($nfce))->handle($issuer);

        $nfce->refresh();
        $this->assertSame('rejeitado', $nfce->status);
        $this->assertSame('215', $nfce->c_stat);
    }

    public function test_transport_error_releases_job(): void
    {
        $empresa = $this->makeEmpresa();
        $nfce = $this->makeNfce($empresa, 2);

        $issuer = Mockery::mock(FiscalIssuerInterface::class);
        $issuer->shouldReceive('emit')
            ->once()
            ->andThrow(new SefazTransportException('cURL timeout'));

        $job = new class($nfce) extends EmitirNfceJob
        {
            public bool $released = false;

            public int $releaseDelay = 0;

            public function release($delay = 0): void
            {
                $this->released = true;
                $this->releaseDelay = (int) $delay;
            }

            public function attempts(): int
            {
                return 1;
            }
        };

        $job->handle($issuer);

        $nfce->refresh();
        $this->assertSame('erro', $nfce->status);
        $this->assertTrue($job->released);
        $this->assertSame(30, $job->releaseDelay);
    }

    private function makeEmpresa(): Empresa
    {
        $user = User::factory()->create();

        return Empresa::create([
            'user_id' => $user->id,
            'cnpj' => '12345678000195',
            'razao_social' => 'TESTE',
            'nome_fantasia' => 'TESTE',
            'inscricao_municipal' => '1',
            'inscricao_estadual' => '123',
            'crt' => 1,
            'nfce_serie' => 1,
            'nfce_ultimo_numero' => 0,
            'nfce_csc_id' => '1',
            'nfce_csc_token' => 'TOKEN',
            'nfce_ambiente' => 2,
            'regime_tributario' => 3,
            'cep' => '69000000',
            'logradouro' => 'RUA',
            'numero' => '1',
            'bairro' => 'CENTRO',
            'uf' => 'AM',
            'cod_ibge_mun' => '1302603',
            'email' => 'a@b.com',
            'telefone' => '92999999999',
        ]);
    }

    private function makeNfce(Empresa $empresa, int $numero): Nfce
    {
        return Nfce::create([
            'empresa_id' => $empresa->id,
            'numero' => $numero,
            'serie' => 1,
            'ambiente' => 2,
            'tp_emis' => 1,
            'status' => 'processando',
            'valor_total' => 1,
            'payload' => [
                'itens' => [[
                    'descricao' => 'X',
                    'ncm' => '22021000',
                    'cfop' => '5102',
                    'unidade' => 'UN',
                    'quantidade' => 1,
                    'valor_unitario' => 1,
                ]],
                'pagamentos' => [['t_pag' => '01', 'v_pag' => 1]],
            ],
        ]);
    }
}
