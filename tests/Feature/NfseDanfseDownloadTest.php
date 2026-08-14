<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\NotaFiscal;
use App\Models\Servico;
use App\Models\User;
use App\Services\NfseNacionalService;
use Tests\TestCase;

class NfseDanfseDownloadTest extends TestCase
{
    public function test_adn_200_devolve_pdf_oficial(): void
    {
        [$user, $nota] = $this->makeNotaAutorizada();

        $this->mock(NfseNacionalService::class, function ($mock) {
            $mock->shouldReceive('downloadDanfse')
                ->once()
                ->andReturn("%PDF-1.4\n%adn-oficial\n");
        });

        $response = $this->actingAs($user)
            ->withSession(['empresa_ativa' => $nota->empresa_id])
            ->get(route('notas.danfse_oficial', $nota->id));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('adn-oficial', $response->getContent());
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
    }

    public function test_adn_502_redireciona_com_aviso_e_nao_entrega_pdf(): void
    {
        [$user, $nota] = $this->makeNotaAutorizada();

        $this->mock(NfseNacionalService::class, function ($mock) {
            $mock->shouldReceive('downloadDanfse')
                ->once()
                ->andThrow(new \Exception('Falha ao baixar DANFSe: 502 - Bad Gateway'));
        });

        $response = $this->actingAs($user)
            ->withSession(['empresa_ativa' => $nota->empresa_id])
            ->get(route('notas.danfse_oficial', $nota->id));

        $response->assertRedirect(route('notas.show', $nota->id));
        $response->assertSessionHasErrors(['download']);
        $this->assertStringContainsString('HTTP 502', session('errors')->first('download'));
        $this->assertFalse(str_starts_with((string) $response->getContent(), '%PDF'));

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('ADN indisponível')
            ->assertSee('HTTP 502')
            ->assertSee('Baixar DANFSe local')
            ->assertSee('Tentar de novo')
            ->assertSee('Abrir Portal Nacional');
    }

    public function test_detalhe_autorizada_mostra_botoes_local_adn_e_portal(): void
    {
        [$user, $nota] = $this->makeNotaAutorizada();

        $response = $this->actingAs($user)
            ->withSession(['empresa_ativa' => $nota->empresa_id])
            ->get(route('notas.show', $nota->id));

        $response->assertOk();
        $response->assertSee('Baixar DANFSe');
        $response->assertSee('Tentar PDF da ADN');
        $response->assertSee('Consultar no Portal Nacional');
        $response->assertSee('ConsultaPublica');
        $response->assertDontSee('Espelho Interno');
        $response->assertDontSee('Baixar DANFSe Oficial');
    }

    /**
     * @return array{0: User, 1: NotaFiscal}
     */
    private function makeNotaAutorizada(): array
    {
        $user = User::factory()->create();
        $empresa = Empresa::create([
            'user_id' => $user->id,
            'cnpj' => '12345678000195',
            'razao_social' => 'TESTE',
            'nome_fantasia' => 'TESTE',
            'inscricao_municipal' => '1',
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

        $user->empresas()->syncWithoutDetaching([
            $empresa->id => ['perfil' => 'admin'],
        ]);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'cnpj' => '12345678909',
            'razao_social' => 'TOMADOR',
            'logradouro' => 'RUA A',
            'numero' => '10',
            'bairro' => 'CENTRO',
            'cep' => '69000000',
            'cidade_codigo' => '1302603',
            'uf' => 'AM',
        ]);

        $servico = Servico::create([
            'empresa_id' => $empresa->id,
            'nome' => 'Serviço Teste',
            'descricao' => 'Serviço de teste',
            'codigo_tributacao_nacional' => '010301',
            'codigo_tributacao_municipal' => '100',
            'codigo_nbs' => '115011000',
            'valor_unitario' => 100,
        ]);

        $nota = NotaFiscal::create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'status' => 'autorizada',
            'ambiente' => 'producao',
            'numero_nfse' => 2,
            'chave_acesso' => '13026032209279540000120000000000000226012551849886',
            'tomador_cnpj' => '12345678909',
            'tomador_nome' => 'TOMADOR',
            'valor_servico' => 100,
            'descricao' => 'Servico teste',
            'emissao' => now(),
            'trib_issqn' => 1,
            'tp_ret_issqn' => 1,
            'xml_autorizado' => (string) file_get_contents(base_path('tests/Fixtures/Nfse/autorizada-minima.xml')),
        ]);

        return [$user, $nota];
    }
}
