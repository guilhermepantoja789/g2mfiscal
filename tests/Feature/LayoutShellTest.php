<?php

namespace Tests\Feature;

use App\Enums\EmpresaPerfil;
use App\Models\Empresa;
use App\Models\User;
use Tests\TestCase;

class LayoutShellTest extends TestCase
{
    public function test_profile_renderiza_shell_com_rail_e_cmdk(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::create([
            'user_id' => $admin->id,
            'cnpj' => '11222333000181',
            'razao_social' => 'SHELL TESTE',
            'cep' => '69000000',
            'logradouro' => 'RUA',
            'numero' => '1',
            'bairro' => 'CENTRO',
            'uf' => 'AM',
            'cod_ibge_mun' => '1302603',
            'regime_tributario' => 3,
        ]);
        $admin->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Admin->value]);

        $response = $this->actingAs($admin)
            ->withSession(['empresa_ativa' => $empresa->id])
            ->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('data-spotlight="nav-rail"', false);
        $response->assertSee('g2m-nav-commands', false);
        $response->assertSee('appShell', false);
        $response->assertDontSee('cdn.jsdelivr.net/npm/alpinejs', false);
    }
}
