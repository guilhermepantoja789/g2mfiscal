<?php

namespace Tests\Feature;

use App\Enums\EmpresaPerfil;
use App\Models\Empresa;
use App\Models\EmpresaModulo;
use App\Models\Nfce;
use App\Models\User;
use Tests\TestCase;

class ContabilAreaTest extends TestCase
{
    public function test_admin_pode_convidar_contador(): void
    {
        [$admin, $empresa] = $this->makeAdminEmpresa();
        $contador = User::factory()->create(['email' => 'contador@example.com']);

        $response = $this->actingAs($admin)
            ->withSession(['empresa_ativa' => $empresa->id])
            ->post(route('equipe.store'), [
                'email' => 'contador@example.com',
                'perfil' => EmpresaPerfil::Contador->value,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('empresa_user', [
            'empresa_id' => $empresa->id,
            'user_id' => $contador->id,
            'perfil' => 'contador',
        ]);
    }

    public function test_contador_nao_pode_emitir_nfce(): void
    {
        [$admin, $empresa] = $this->makeAdminEmpresa();
        $contador = User::factory()->create();
        $contador->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Contador->value]);

        $response = $this->actingAs($contador)
            ->withSession(['empresa_ativa' => $empresa->id])
            ->get(route('nfces.create'));

        $response->assertForbidden();
    }

    public function test_contador_acessa_hub_quando_modulo_ativo(): void
    {
        [$admin, $empresa] = $this->makeAdminEmpresa();
        $empresa->definirModulo(EmpresaModulo::MODULO_CONTABIL, true);

        $contador = User::factory()->create();
        $contador->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Contador->value]);

        $response = $this->actingAs($contador)
            ->withSession(['empresa_ativa' => $empresa->id])
            ->get(route('contabil.dashboard'));

        $response->assertOk();
    }

    public function test_operador_nao_acessa_hub_contabil(): void
    {
        [$admin, $empresa] = $this->makeAdminEmpresa();
        $empresa->definirModulo(EmpresaModulo::MODULO_CONTABIL, true);

        $operador = User::factory()->create();
        $operador->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Operador->value]);

        $response = $this->actingAs($operador)
            ->withSession(['empresa_ativa' => $empresa->id])
            ->get(route('contabil.dashboard'));

        $response->assertForbidden();
    }

    public function test_hub_bloqueado_sem_modulo_contabil(): void
    {
        [$admin, $empresa] = $this->makeAdminEmpresa();
        // opt-in: sem registro = desabilitado

        $contador = User::factory()->create();
        $contador->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Contador->value]);

        $response = $this->actingAs($contador)
            ->withSession(['empresa_ativa' => $empresa->id])
            ->get(route('contabil.dashboard'));

        $response->assertForbidden();
    }

    public function test_entrar_como_contador_redireciona_para_hub(): void
    {
        [$admin, $empresa] = $this->makeAdminEmpresa();
        $empresa->definirModulo(EmpresaModulo::MODULO_CONTABIL, true);

        $contador = User::factory()->create();
        $contador->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Contador->value]);

        $response = $this->actingAs($contador)
            ->get(route('empresas.entrar', $empresa));

        $response->assertRedirect(route('contabil.dashboard'));
    }

    public function test_export_csv_smoke(): void
    {
        [$admin, $empresa] = $this->makeAdminEmpresa();
        $empresa->definirModulo(EmpresaModulo::MODULO_CONTABIL, true);

        $contador = User::factory()->create();
        $contador->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Contador->value]);

        Nfce::create([
            'empresa_id' => $empresa->id,
            'numero' => 1,
            'serie' => 1,
            'ambiente' => 2,
            'tp_emis' => 1,
            'status' => 'autorizada',
            'chave' => '35260712345678000195550010000000011000000010',
            'valor_total' => 15.50,
            'xml_autorizado' => '<nfeProc/>',
        ]);

        $response = $this->actingAs($contador)
            ->withSession(['empresa_ativa' => $empresa->id])
            ->get(route('contabil.export.csv', [
                'tipo' => 'nfce',
                'data_inicio' => now()->startOfMonth()->format('Y-m-d'),
                'data_fim' => now()->endOfMonth()->format('Y-m-d'),
            ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('chave', $content);
        $this->assertStringContainsString('autorizada', $content);
    }

    public function test_contador_pode_listar_nfces_leitura(): void
    {
        [$admin, $empresa] = $this->makeAdminEmpresa();
        $contador = User::factory()->create();
        $contador->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Contador->value]);

        $response = $this->actingAs($contador)
            ->withSession(['empresa_ativa' => $empresa->id])
            ->get(route('nfces.index'));

        $response->assertOk();
    }

    /**
     * @return array{0: User, 1: Empresa}
     */
    private function makeAdminEmpresa(): array
    {
        $user = User::factory()->create();
        $empresa = Empresa::create([
            'user_id' => $user->id,
            'cnpj' => '12345678000195',
            'razao_social' => 'EMP CONTABIL',
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
            'crt' => 1,
        ]);
        $user->empresas()->attach($empresa->id, ['perfil' => EmpresaPerfil::Admin->value]);

        return [$user, $empresa];
    }
}
