<?php

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\NotaFiscal;
use App\Models\Servico;
use App\Models\User;
use App\Services\NfseDpsCounterSync;
use App\Services\NfseDpsNumero;
use Tests\TestCase;

class NfseDpsNumeroTest extends TestCase
{
    public function test_reservar_nao_reutiliza_numero_dps_ja_persistido(): void
    {
        $empresa = $this->makeEmpresa();
        $empresa->update(['nfse_dps_ultimo_numero' => 2]);

        $this->makeNota($empresa, [
            'status' => 'autorizada',
            'numero_dps' => 41,
        ]);

        $proximo = NfseDpsNumero::reservar($empresa->fresh());

        $this->assertSame(42, $proximo);
        $this->assertSame(42, (int) $empresa->fresh()->nfse_dps_ultimo_numero);
    }

    public function test_reservar_considera_id_de_nota_autorizada_sem_dps(): void
    {
        $empresa = $this->makeEmpresa();
        $empresa->update(['nfse_dps_ultimo_numero' => 0]);

        $nota = $this->makeNota($empresa, [
            'status' => 'autorizada',
            'numero_dps' => null,
        ]);

        $proximo = NfseDpsNumero::reservar($empresa->fresh());

        $this->assertSame(((int) $nota->id) + 1, $proximo);
    }

    public function test_sync_preenche_dps_nulo_e_sobe_contador(): void
    {
        $empresa = $this->makeEmpresa();
        $empresa->update(['nfse_dps_ultimo_numero' => 0]);

        $nota = $this->makeNota($empresa, [
            'status' => 'autorizada',
            'numero_dps' => null,
        ]);

        $result = (new NfseDpsCounterSync)->sync(null, false);

        $this->assertSame(1, $result['backfilled']);
        $this->assertSame((int) $nota->id, (int) $nota->fresh()->numero_dps);
        $this->assertSame((int) $nota->id, (int) $empresa->fresh()->nfse_dps_ultimo_numero);
    }

    public function test_sync_dry_run_nao_grava(): void
    {
        $empresa = $this->makeEmpresa();
        $empresa->update(['nfse_dps_ultimo_numero' => 2]);
        $this->makeNota($empresa, [
            'status' => 'autorizada',
            'numero_dps' => null,
        ]);

        (new NfseDpsCounterSync)->sync(null, true);

        $this->assertNull(NotaFiscal::query()->where('empresa_id', $empresa->id)->value('numero_dps'));
        $this->assertSame(2, (int) $empresa->fresh()->nfse_dps_ultimo_numero);
    }

    private function makeEmpresa(): Empresa
    {
        $user = User::factory()->create();

        return Empresa::create([
            'user_id' => $user->id,
            'cnpj' => fake()->unique()->numerify('##############'),
            'razao_social' => 'TESTE DPS',
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
            'nfse_dps_ultimo_numero' => 0,
        ]);
    }

    private function makeNota(Empresa $empresa, array $overrides): NotaFiscal
    {
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

        return NotaFiscal::create(array_merge([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'status' => 'criada',
            'ambiente' => 'homologacao',
            'tomador_cnpj' => '12345678909',
            'tomador_nome' => 'TOMADOR',
            'valor_servico' => 100,
            'descricao' => 'Servico teste',
            'emissao' => now(),
            'trib_issqn' => 1,
            'tp_ret_issqn' => 1,
        ], $overrides));
    }
}
