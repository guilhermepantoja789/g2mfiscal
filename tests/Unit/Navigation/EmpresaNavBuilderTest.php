<?php

namespace Tests\Unit\Navigation;

use App\Enums\EmpresaPerfil;
use App\Models\Empresa;
use App\Models\EmpresaModulo;
use App\Models\User;
use App\Support\Navigation\EmpresaNavBuilder;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class EmpresaNavBuilderTest extends TestCase
{
    public function test_monta_grupos_conforme_modulos_e_perfil(): void
    {
        $admin = User::factory()->create();
        $empresa = $this->criarEmpresa($admin);
        $admin->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Admin->value]);

        foreach ([
            EmpresaModulo::MODULO_ERP,
            EmpresaModulo::MODULO_PDV,
            EmpresaModulo::MODULO_FINANCEIRO_GERENCIAL,
            EmpresaModulo::MODULO_CONTABIL,
        ] as $modulo) {
            EmpresaModulo::query()->create([
                'empresa_id' => $empresa->id,
                'modulo' => $modulo,
                'ativo' => true,
            ]);
        }

        Auth::login($admin);
        session(['empresa_ativa' => $empresa->id]);

        $nav = app(EmpresaNavBuilder::class)->build();

        $ids = collect($nav['groups'])->pluck('id')->all();
        $this->assertContains('inicio', $ids);
        $this->assertContains('contabil', $ids);
        $this->assertContains('fiscal_servicos', $ids);
        $this->assertContains('fiscal_produtos', $ids);
        $this->assertContains('vendas', $ids);
        $this->assertContains('financeiro', $ids);

        $this->assertNotEmpty($nav['command_items']);
        $this->assertTrue(collect($nav['utility'])->contains(fn ($u) => $u['id'] === 'config'));
        $this->assertFalse(collect($nav['utility'])->contains(fn ($u) => $u['id'] === 'vinculos'));
    }

    public function test_platform_admin_ve_vinculos_na_utility(): void
    {
        $platform = User::factory()->create();
        $platform->forceFill(['is_platform_admin' => true])->save();
        $empresa = $this->criarEmpresa($platform);

        Auth::login($platform);
        session(['empresa_ativa' => $empresa->id]);

        $nav = app(EmpresaNavBuilder::class)->build();
        $this->assertTrue(collect($nav['utility'])->contains(fn ($u) => $u['id'] === 'vinculos'));
    }

    public function test_contador_nao_ve_cobrancas_nem_config(): void
    {
        $admin = User::factory()->create();
        $contador = User::factory()->create();
        $empresa = $this->criarEmpresa($admin);
        $admin->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Admin->value]);
        $contador->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Contador->value]);

        EmpresaModulo::query()->create([
            'empresa_id' => $empresa->id,
            'modulo' => EmpresaModulo::MODULO_CONTABIL,
            'ativo' => true,
        ]);

        Auth::login($contador);
        session(['empresa_ativa' => $empresa->id]);

        $nav = app(EmpresaNavBuilder::class)->build();
        $servicos = collect($nav['groups'])->firstWhere('id', 'fiscal_servicos');
        $labels = collect($servicos['items'] ?? [])->pluck('label');

        $this->assertFalse($labels->contains('Cobranças'));
        $this->assertFalse(collect($nav['utility'])->contains(fn ($u) => $u['id'] === 'config'));
        $this->assertTrue(collect($nav['groups'])->contains(fn ($g) => $g['id'] === 'contabil'));
    }

    private function criarEmpresa(User $dono): Empresa
    {
        return Empresa::create([
            'user_id' => $dono->id,
            'cnpj' => '98765432000198',
            'razao_social' => 'NAV TESTE',
            'cep' => '69000000',
            'logradouro' => 'RUA',
            'numero' => '1',
            'bairro' => 'CENTRO',
            'uf' => 'AM',
            'cod_ibge_mun' => '1302603',
            'regime_tributario' => 3,
        ]);
    }
}
