<?php

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\NotaFiscal;
use App\Models\Servico;
use App\Models\User;
use App\Services\NfseAmbiente;
use App\Services\NfseEmitPayloadBuilder;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Tests\TestCase;

class NfseEmitPayloadBuilderTest extends TestCase
{

    public function test_builds_payload_with_ambiente_serie(): void
    {
        Config::set('services.nfse_nacional.tp_amb', 2);

        $nota = $this->makeNota();
        $payload = NfseEmitPayloadBuilder::fromNota($nota);

        $this->assertSame($nota->id, $payload['numero']);
        $this->assertSame(NfseAmbiente::serie(), $payload['serie']);
        $this->assertSame('99', $payload['serie']);
        $this->assertSame('RUA A', $payload['tomador_endereco']);
        $this->assertSame('010301', $payload['servico_nbs']);
    }

    public function test_rejects_incomplete_cliente(): void
    {
        $nota = $this->makeNota(completo: false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Endereço do tomador incompleto');
        NfseEmitPayloadBuilder::fromNota($nota);
    }

    private function makeNota(bool $completo = true): NotaFiscal
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

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'cnpj' => '12345678909',
            'razao_social' => 'TOMADOR',
            'logradouro' => $completo ? 'RUA A' : null,
            'numero' => '10',
            'bairro' => $completo ? 'CENTRO' : null,
            'cep' => $completo ? '69000000' : null,
            'cidade_codigo' => $completo ? '1302603' : null,
            'uf' => $completo ? 'AM' : null,
        ]);

        $servico = Servico::create([
            'empresa_id' => $empresa->id,
            'nome' => 'Serviço Teste',
            'descricao' => 'Serviço de teste',
            'codigo_tributacao_nacional' => '010301',
            'codigo_tributacao_municipal' => '100',
            'valor_unitario' => 100,
        ]);

        return NotaFiscal::create([
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
        ]);
    }
}
