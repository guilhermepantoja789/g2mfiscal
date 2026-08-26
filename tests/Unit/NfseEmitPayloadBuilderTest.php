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

        $this->assertSame((int) $nota->numero_dps, $payload['numero']);
        $this->assertSame(NfseAmbiente::serie(), $payload['serie']);
        $this->assertSame('99', $payload['serie']);
        $this->assertSame('RUA A', $payload['tomador_endereco']);
        $this->assertSame('010301', $payload['servico_nbs']);
        $this->assertSame('115011000', $payload['servico_cnbs']);
        $this->assertSame('100301', $payload['c_ind_op']);
        $this->assertSame('000', $payload['cst_ibscbs']);
        $this->assertSame('000001', $payload['c_class_trib']);
        $this->assertSame('0', $payload['fin_nfse']);
        $this->assertNull($payload['ind_final']); // CPF → derivado no builder XML
    }

    public function test_nota_override_wins_over_servico(): void
    {
        Config::set('services.nfse_nacional.tp_amb', 2);

        $nota = $this->makeNota();
        $nota->update([
            'c_ind_op' => '050101',
            'cst_ibscbs' => '410',
            'c_class_trib' => '410001',
            'ind_final' => '0',
        ]);

        $payload = NfseEmitPayloadBuilder::fromNota($nota->fresh(['cliente', 'servico']));

        $this->assertSame('050101', $payload['c_ind_op']);
        $this->assertSame('410', $payload['cst_ibscbs']);
        $this->assertSame('410001', $payload['c_class_trib']);
        $this->assertSame('0', $payload['ind_final']);
    }

    public function test_rejects_incomplete_cliente(): void
    {
        $nota = $this->makeNota(completo: false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Endereço do tomador incompleto');
        NfseEmitPayloadBuilder::fromNota($nota);
    }

    public function test_rejects_missing_cnbs(): void
    {
        $nota = $this->makeNota();
        $nota->servico->update(['codigo_nbs' => null]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('código NBS');
        NfseEmitPayloadBuilder::fromNota($nota->fresh(['cliente', 'servico']));
    }

    public function test_rejects_missing_numero_dps(): void
    {
        $nota = $this->makeNota();
        $nota->update(['numero_dps' => null]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nDPS');
        NfseEmitPayloadBuilder::fromNota($nota->fresh(['cliente', 'servico']));
    }

    public function test_normalize_cnbs_accepts_masked_value(): void
    {
        $this->assertSame('115011000', NfseEmitPayloadBuilder::normalizeCnbs('1.1501.10.00'));
        $this->assertSame('115011000', NfseEmitPayloadBuilder::normalizeCnbs('115011000'));
        $this->assertNull(NfseEmitPayloadBuilder::normalizeCnbs('1.01'));
        $this->assertNull(NfseEmitPayloadBuilder::normalizeCnbs(''));
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
            'codigo_nbs' => '115011000',
            'valor_unitario' => 100,
            'fin_nfse' => '0',
            'c_ind_op' => '100301',
            'cst_ibscbs' => '000',
            'c_class_trib' => '000001',
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
            'numero_dps' => 88,
            'descricao' => 'Servico teste',
            'emissao' => now(),
            'trib_issqn' => 1,
            'tp_ret_issqn' => 1,
        ]);
    }
}
