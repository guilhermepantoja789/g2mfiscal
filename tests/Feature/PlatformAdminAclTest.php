<?php

namespace Tests\Feature;

use App\Enums\EmpresaPerfil;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PlatformAdminAclTest extends TestCase
{
    public function test_platform_admin_ve_todas_empresas_na_selecao(): void
    {
        $dono = User::factory()->create();
        $platform = User::factory()->create();
        $platform->forceFill(['is_platform_admin' => true])->save();

        $empresa = $this->criarEmpresa($dono);
        $dono->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Admin->value]);

        $response = $this->actingAs($platform)->get(route('empresas.selecao'));

        $response->assertOk();
        $response->assertSee($empresa->razao_social);
        $response->assertSee('Visão plataforma');
    }

    public function test_platform_admin_entra_sem_membership(): void
    {
        $dono = User::factory()->create();
        $platform = User::factory()->create();
        $platform->forceFill(['is_platform_admin' => true])->save();

        $empresa = $this->criarEmpresa($dono);
        $dono->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Admin->value]);

        $response = $this->actingAs($platform)->get(route('empresas.entrar', $empresa));

        $response->assertRedirect(route('dashboard'));
        $this->assertEquals($empresa->id, session('empresa_ativa'));
    }

    public function test_equipe_rejeita_promover_admin(): void
    {
        [$admin, $empresa] = $this->makeAdminEmpresa();
        $operador = User::factory()->create(['email' => 'op@example.com']);

        $response = $this->actingAs($admin)
            ->withSession(['empresa_ativa' => $empresa->id])
            ->post(route('equipe.store'), [
                'email' => 'op@example.com',
                'perfil' => EmpresaPerfil::Admin->value,
            ]);

        $response->assertSessionHasErrors('perfil');
        $this->assertDatabaseMissing('empresa_user', [
            'empresa_id' => $empresa->id,
            'user_id' => $operador->id,
        ]);
    }

    public function test_equipe_nao_demove_nem_remove_admin(): void
    {
        [$admin, $empresa] = $this->makeAdminEmpresa();
        $outroAdmin = User::factory()->create();
        $outroAdmin->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Admin->value]);

        $demote = $this->actingAs($admin)
            ->withSession(['empresa_ativa' => $empresa->id])
            ->put(route('equipe.updateRole', $outroAdmin->id), [
                'perfil' => EmpresaPerfil::Operador->value,
            ]);
        $demote->assertSessionHasErrors('erro');

        $remove = $this->actingAs($admin)
            ->withSession(['empresa_ativa' => $empresa->id])
            ->delete(route('equipe.destroy', $outroAdmin->id));
        $remove->assertSessionHasErrors('erro');

        $this->assertDatabaseHas('empresa_user', [
            'empresa_id' => $empresa->id,
            'user_id' => $outroAdmin->id,
            'perfil' => 'admin',
        ]);
    }

    public function test_operador_de_b_nao_acessa_config_de_b_com_sessao_de_a_admin(): void
    {
        $user = User::factory()->create();
        $empresaA = $this->criarEmpresa($user, '11111111000191');
        $empresaB = $this->criarEmpresa($user, '22222222000172');
        $user->empresas()->attach($empresaA->id, ['perfil' => EmpresaPerfil::Admin->value]);
        $user->empresas()->attach($empresaB->id, ['perfil' => EmpresaPerfil::Operador->value]);

        $response = $this->actingAs($user)
            ->withSession(['empresa_ativa' => $empresaA->id])
            ->get(route('empresas.configuracao', $empresaB));

        $response->assertForbidden();
    }

    public function test_vinculos_platform_attach_operador(): void
    {
        $dono = User::factory()->create();
        $platform = User::factory()->create();
        $platform->forceFill(['is_platform_admin' => true])->save();
        $alvo = User::factory()->create();

        $empresa = $this->criarEmpresa($dono);
        $dono->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Admin->value]);

        $forbidden = $this->actingAs($dono)->get(route('admin.vinculos.index'));
        $forbidden->assertForbidden();

        $ok = $this->actingAs($platform)->get(route('admin.vinculos.index'));
        $ok->assertOk();

        $store = $this->actingAs($platform)->post(route('admin.vinculos.store'), [
            'user_id' => $alvo->id,
            'empresa_id' => $empresa->id,
            'perfil' => EmpresaPerfil::Operador->value,
        ]);
        $store->assertRedirect();
        $this->assertDatabaseHas('empresa_user', [
            'empresa_id' => $empresa->id,
            'user_id' => $alvo->id,
            'perfil' => 'operador',
        ]);
    }

    public function test_artisan_platform_admin_e_attach_empresa(): void
    {
        $user = User::factory()->create(['email' => 'ops@example.com']);
        $dono = User::factory()->create();
        $empresa = $this->criarEmpresa($dono);

        $this->assertFalse($user->fresh()->isPlatformAdmin());

        Artisan::call('user:platform-admin', ['email' => 'ops@example.com']);
        $this->assertTrue($user->fresh()->isPlatformAdmin());

        Artisan::call('user:attach-empresa', [
            'email' => 'ops@example.com',
            'empresa_id' => $empresa->id,
            '--perfil' => 'admin',
        ]);
        $this->assertDatabaseHas('empresa_user', [
            'empresa_id' => $empresa->id,
            'user_id' => $user->id,
            'perfil' => 'admin',
        ]);

        Artisan::call('user:platform-admin', [
            'email' => 'ops@example.com',
            '--revoke' => true,
        ]);
        $this->assertFalse($user->fresh()->isPlatformAdmin());
    }

    /**
     * @return array{0: User, 1: Empresa}
     */
    private function makeAdminEmpresa(): array
    {
        $admin = User::factory()->create();
        $empresa = $this->criarEmpresa($admin);
        $admin->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Admin->value]);

        return [$admin, $empresa];
    }

    private function criarEmpresa(User $dono, string $cnpj = '12345678000195'): Empresa
    {
        return Empresa::create([
            'user_id' => $dono->id,
            'cnpj' => $cnpj,
            'razao_social' => 'PLATFORM TEST '.$cnpj,
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
