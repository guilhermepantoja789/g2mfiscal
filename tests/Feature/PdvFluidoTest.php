<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\DocumentoComercial;
use App\Models\Empresa;
use App\Models\Nfce;
use App\Models\User;
use Tests\TestCase;

class PdvFluidoTest extends TestCase
{

    public function test_busca_clientes_json_filtra_por_empresa_e_termo(): void
    {
        [$user, $empresa] = $this->makeUserEmpresa();
        $outra = $this->makeEmpresaFor($user, '98765432000198');

        Cliente::create([
            'empresa_id' => $empresa->id,
            'razao_social' => 'Maria Silva',
            'cnpj' => '52998224725',
        ]);
        Cliente::create([
            'empresa_id' => $empresa->id,
            'razao_social' => 'João Souza',
            'cnpj' => '39053344705',
        ]);
        Cliente::create([
            'empresa_id' => $outra->id,
            'razao_social' => 'Maria Outra Empresa',
            'cnpj' => '11144477735',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['empresa_ativa' => $empresa->id])
            ->getJson(route('clientes.buscar', ['q' => 'Maria']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertSame('Maria Silva', $data[0]['razao_social']);
        $this->assertSame('52998224725', $data[0]['cnpj']);
    }

    public function test_status_venda_sem_imprimir_quando_processando(): void
    {
        [$user, $empresa] = $this->makeUserEmpresa();

        $doc = DocumentoComercial::create([
            'empresa_id' => $empresa->id,
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'status' => DocumentoComercial::STATUS_PROCESSANDO_FISCAL,
            'valor_total' => 10,
            'pago_avista' => true,
        ]);

        Nfce::create([
            'empresa_id' => $empresa->id,
            'documento_comercial_id' => $doc->id,
            'numero' => 1,
            'serie' => 1,
            'ambiente' => 2,
            'tp_emis' => 1,
            'status' => 'processando',
            'valor_total' => 10,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['empresa_ativa' => $empresa->id])
            ->getJson(route('pdv.vendas.status', $doc->id));

        $response->assertOk()
            ->assertJsonPath('pode_imprimir', false)
            ->assertJsonPath('imprimir_url', null)
            ->assertJsonPath('documento_status', DocumentoComercial::STATUS_PROCESSANDO_FISCAL);
    }

    public function test_status_venda_com_imprimir_quando_autorizada(): void
    {
        [$user, $empresa] = $this->makeUserEmpresa();

        $doc = DocumentoComercial::create([
            'empresa_id' => $empresa->id,
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'status' => DocumentoComercial::STATUS_AUTORIZADO,
            'valor_total' => 10,
            'pago_avista' => true,
        ]);

        $nfce = Nfce::create([
            'empresa_id' => $empresa->id,
            'documento_comercial_id' => $doc->id,
            'numero' => 7,
            'serie' => 1,
            'ambiente' => 2,
            'tp_emis' => 1,
            'status' => 'autorizada',
            'xml_autorizado' => '<nfeProc/>',
            'valor_total' => 10,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['empresa_ativa' => $empresa->id])
            ->getJson(route('pdv.vendas.status', $doc->id));

        $response->assertOk()
            ->assertJsonPath('pode_imprimir', true)
            ->assertJsonPath('nfce_numero', 7)
            ->assertJsonPath('terminal', true);

        $this->assertSame(route('nfces.imprimir', $nfce->id), $response->json('imprimir_url'));
    }

    /**
     * @return array{0: User, 1: Empresa}
     */
    private function makeUserEmpresa(): array
    {
        $user = User::factory()->create();
        $empresa = $this->makeEmpresaFor($user, '12345678000195');

        return [$user, $empresa];
    }

    private function makeEmpresaFor(User $user, string $cnpj): Empresa
    {
        $empresa = Empresa::create([
            'user_id' => $user->id,
            'cnpj' => $cnpj,
            'razao_social' => 'EMP TESTE '.$cnpj,
            'cep' => '69000000',
            'logradouro' => 'RUA',
            'numero' => '1',
            'bairro' => 'CENTRO',
            'uf' => 'AM',
            'cod_ibge_mun' => '1302603',
            'regime_tributario' => 3,
            'nfce_serie' => 1,
            'nfce_ultimo_numero' => 0,
            'nfce_ambiente' => 2,
            'nfce_csc_id' => '1',
            'nfce_csc_token' => 'TOKEN',
            'crt' => 1,
            'inscricao_estadual' => '123',
        ]);

        $user->empresas()->syncWithoutDetaching([
            $empresa->id => ['perfil' => 'admin'],
        ]);

        return $empresa;
    }
}
