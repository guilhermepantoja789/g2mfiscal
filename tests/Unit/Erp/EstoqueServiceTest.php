<?php

namespace Tests\Unit\Erp;

use App\Models\Empresa;
use App\Models\Produto;
use App\Models\User;
use App\Services\Erp\EstoqueService;
use RuntimeException;
use Tests\TestCase;

class EstoqueServiceTest extends TestCase
{

    public function test_entrada_e_saida_atualizam_saldo(): void
    {
        $produto = $this->makeProduto(0);

        $service = app(EstoqueService::class);
        $service->entrada($produto, 10, 5.0, null, 'teste');
        $produto->refresh();
        $this->assertEquals(10, (float) $produto->estoque_atual);
        $this->assertEquals(5.0, (float) $produto->custo_medio);

        $service->saida($produto->fresh(), 3, 5.0, null, 'teste');
        $produto->refresh();
        $this->assertEquals(7, (float) $produto->estoque_atual);
    }

    public function test_saida_bloqueia_estoque_negativo(): void
    {
        $this->expectException(RuntimeException::class);

        $produto = $this->makeProduto(1);
        app(EstoqueService::class)->saida($produto, 2, 1, null, 'teste');
    }

    private function makeProduto(float $estoque): Produto
    {
        $user = User::factory()->create();
        $empresa = Empresa::create([
            'user_id' => $user->id,
            'cnpj' => '12345678000195',
            'razao_social' => 'EMP',
            'cep' => '69000000',
            'logradouro' => 'RUA',
            'numero' => '1',
            'bairro' => 'CENTRO',
            'uf' => 'AM',
            'cod_ibge_mun' => '1302603',
            'regime_tributario' => 3,
        ]);

        return Produto::create([
            'empresa_id' => $empresa->id,
            'descricao' => 'Produto Teste',
            'ncm' => '22021000',
            'cfop' => '5102',
            'csosn' => '102',
            'unidade' => 'UN',
            'preco_venda' => 10,
            'custo_medio' => 0,
            'estoque_atual' => $estoque,
            'controla_estoque' => true,
            'ativo' => true,
        ]);
    }
}
