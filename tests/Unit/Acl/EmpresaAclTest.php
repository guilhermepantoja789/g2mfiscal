<?php

namespace Tests\Unit\Acl;

use App\Enums\EmpresaPerfil;
use App\Models\Empresa;
use App\Models\User;
use App\Services\Acl\EmpresaAcl;
use Tests\TestCase;

class EmpresaAclTest extends TestCase
{
    public function test_perfis_e_permissoes(): void
    {
        $admin = User::factory()->create();
        $contador = User::factory()->create();
        $operador = User::factory()->create();

        $empresa = $this->criarEmpresa($admin);
        $admin->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Admin->value]);
        $contador->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Contador->value]);
        $operador->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Operador->value]);

        $acl = app(EmpresaAcl::class);

        $this->assertTrue($acl->podeAdministrar($admin, $empresa->id));
        $this->assertFalse($acl->podeAdministrar($contador, $empresa->id));

        $this->assertTrue($acl->podeEscreverOperacional($admin, $empresa->id));
        $this->assertTrue($acl->podeEscreverOperacional($operador, $empresa->id));
        $this->assertFalse($acl->podeEscreverOperacional($contador, $empresa->id));

        $this->assertTrue($acl->podeAcessarContabil($admin, $empresa->id));
        $this->assertTrue($acl->podeAcessarContabil($contador, $empresa->id));
        $this->assertFalse($acl->podeAcessarContabil($operador, $empresa->id));

        $this->assertTrue($contador->isContadorNaEmpresa($empresa->id));
        $this->assertTrue($admin->podeAdministrarEmpresa($empresa->id));
    }

    public function test_platform_admin_ve_todas_empresas_e_administra(): void
    {
        $dono = User::factory()->create();
        $platform = User::factory()->create();
        $platform->forceFill(['is_platform_admin' => true])->save();

        $empresaA = $this->criarEmpresa($dono, '11111111000191');
        $empresaB = $this->criarEmpresa($dono, '22222222000172');
        $dono->empresas()->attach($empresaA->id, ['perfil' => EmpresaPerfil::Admin->value]);

        $acl = app(EmpresaAcl::class);

        $this->assertTrue($acl->isPlatformAdmin($platform));
        $this->assertTrue($acl->podeAcessarEmpresa($platform, $empresaA->id));
        $this->assertTrue($acl->podeAcessarEmpresa($platform, $empresaB->id));
        $this->assertFalse($acl->pertenceAEmpresa($platform, $empresaA->id));
        $this->assertTrue($acl->podeAdministrar($platform, $empresaA->id));
        $this->assertTrue($acl->podeEscreverOperacional($platform, $empresaB->id));

        $visiveis = $acl->empresasVisiveis($platform);
        $this->assertTrue($visiveis->contains('id', $empresaA->id));
        $this->assertTrue($visiveis->contains('id', $empresaB->id));

        $visiveisDono = $acl->empresasVisiveis($dono);
        $this->assertTrue($visiveisDono->contains('id', $empresaA->id));
        $this->assertFalse($visiveisDono->contains('id', $empresaB->id));
    }

    private function criarEmpresa(User $dono, string $cnpj = '12345678000195'): Empresa
    {
        return Empresa::create([
            'user_id' => $dono->id,
            'cnpj' => $cnpj,
            'razao_social' => 'ACL TESTE '.$cnpj,
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
