<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Produto;
use App\Services\Erp\FormaPagamentoService;
use Illuminate\Database\Seeder;

/**
 * Catálogo demo de produtos + formas de pagamento para empresas existentes.
 */
class ProdutoDemoSeeder extends Seeder
{
    public function run(): void
    {
        $empresas = Empresa::query()->get();
        if ($empresas->isEmpty()) {
            $this->command?->warn('Nenhuma empresa — nada a semear.');

            return;
        }

        $formas = app(FormaPagamentoService::class);

        foreach ($empresas as $empresa) {
            $formas->garantirDefaults($empresa);
            $criados = 0;

            foreach ($this->catalogo() as $item) {
                $produto = Produto::query()->firstOrCreate(
                    [
                        'empresa_id' => $empresa->id,
                        'sku' => $item['sku'],
                    ],
                    [
                        'ean' => $item['ean'],
                        'descricao' => $item['descricao'],
                        'ncm' => $item['ncm'],
                        'cfop' => '5102',
                        'csosn' => '102',
                        'unidade' => $item['unidade'],
                        'preco_venda' => $item['preco_venda'],
                        'custo_medio' => round($item['preco_venda'] * 0.6, 4),
                        'estoque_atual' => $item['estoque'],
                        'controla_estoque' => true,
                        'ativo' => true,
                    ],
                );

                if ($produto->wasRecentlyCreated) {
                    $criados++;
                }
            }

            $this->command?->info("Empresa #{$empresa->id}: +{$criados} produtos, formas OK.");
        }
    }

    /**
     * @return list<array{sku: string, ean: ?string, descricao: string, ncm: string, unidade: string, preco_venda: float, estoque: float}>
     */
    private function catalogo(): array
    {
        return [
            ['sku' => 'AGUA-500', 'ean' => '7891000100103', 'descricao' => 'Água mineral sem gás 500ml', 'ncm' => '22011000', 'unidade' => 'UN', 'preco_venda' => 2.79, 'estoque' => 120],
            ['sku' => 'REFRI-350', 'ean' => '7894900011517', 'descricao' => 'Refrigerante cola lata 350ml', 'ncm' => '22021000', 'unidade' => 'UN', 'preco_venda' => 4.50, 'estoque' => 80],
            ['sku' => 'SUCO-1L', 'ean' => '7891000100202', 'descricao' => 'Suco de laranja 1L', 'ncm' => '20091200', 'unidade' => 'UN', 'preco_venda' => 9.90, 'estoque' => 40],
            ['sku' => 'CAFE-500', 'ean' => '7891000100301', 'descricao' => 'Café torrado e moído 500g', 'ncm' => '09012100', 'unidade' => 'UN', 'preco_venda' => 18.90, 'estoque' => 35],
            ['sku' => 'ACUCAR-1K', 'ean' => '7891000100400', 'descricao' => 'Açúcar cristal 1kg', 'ncm' => '17019900', 'unidade' => 'UN', 'preco_venda' => 5.49, 'estoque' => 60],
            ['sku' => 'ARROZ-5K', 'ean' => '7891000100509', 'descricao' => 'Arroz tipo 1 5kg', 'ncm' => '10063021', 'unidade' => 'UN', 'preco_venda' => 27.90, 'estoque' => 45],
            ['sku' => 'FEIJAO-1K', 'ean' => '7891000100608', 'descricao' => 'Feijão carioca 1kg', 'ncm' => '07133399', 'unidade' => 'UN', 'preco_venda' => 8.90, 'estoque' => 50],
            ['sku' => 'OLEO-900', 'ean' => '7891000100707', 'descricao' => 'Óleo de soja 900ml', 'ncm' => '15071000', 'unidade' => 'UN', 'preco_venda' => 7.49, 'estoque' => 55],
            ['sku' => 'LEITE-1L', 'ean' => '7891000100806', 'descricao' => 'Leite UHT integral 1L', 'ncm' => '04012010', 'unidade' => 'UN', 'preco_venda' => 5.99, 'estoque' => 70],
            ['sku' => 'PAO-500', 'ean' => '7891000100905', 'descricao' => 'Pão de forma integral 500g', 'ncm' => '19059090', 'unidade' => 'UN', 'preco_venda' => 9.50, 'estoque' => 30],
            ['sku' => 'BISC-140', 'ean' => '7891000101001', 'descricao' => 'Biscoito recheado 140g', 'ncm' => '19053100', 'unidade' => 'UN', 'preco_venda' => 3.99, 'estoque' => 90],
            ['sku' => 'SABON-90', 'ean' => '7891000101100', 'descricao' => 'Sabonete em barra 90g', 'ncm' => '34011190', 'unidade' => 'UN', 'preco_venda' => 2.79, 'estoque' => 100],
            ['sku' => 'DETER-500', 'ean' => '7891000101209', 'descricao' => 'Detergente líquido 500ml', 'ncm' => '34025000', 'unidade' => 'UN', 'preco_venda' => 2.49, 'estoque' => 85],
            ['sku' => 'PAPEL-12', 'ean' => '7891000101308', 'descricao' => 'Papel higiênico 12 rolos', 'ncm' => '48181000', 'unidade' => 'UN', 'preco_venda' => 22.90, 'estoque' => 25],
            ['sku' => 'CADER-80', 'ean' => '7891000101407', 'descricao' => 'Caderno universitário 80 folhas', 'ncm' => '48202000', 'unidade' => 'UN', 'preco_venda' => 14.90, 'estoque' => 40],
            ['sku' => 'CANETA-AZ', 'ean' => '7891000101506', 'descricao' => 'Caneta esferográfica azul', 'ncm' => '96081000', 'unidade' => 'UN', 'preco_venda' => 1.99, 'estoque' => 200],
            ['sku' => 'FONE-BT', 'ean' => '7891000101605', 'descricao' => 'Fone de ouvido Bluetooth', 'ncm' => '85183000', 'unidade' => 'UN', 'preco_venda' => 89.90, 'estoque' => 15],
            ['sku' => 'CABO-USBC', 'ean' => '7891000101704', 'descricao' => 'Cabo USB-C 1m', 'ncm' => '85444200', 'unidade' => 'UN', 'preco_venda' => 19.90, 'estoque' => 40],
            ['sku' => 'MOUSE-USB', 'ean' => '7891000101803', 'descricao' => 'Mouse óptico USB', 'ncm' => '84716053', 'unidade' => 'UN', 'preco_venda' => 39.90, 'estoque' => 22],
            ['sku' => 'TECLADO', 'ean' => '7891000101902', 'descricao' => 'Teclado USB ABNT2', 'ncm' => '84716052', 'unidade' => 'UN', 'preco_venda' => 79.90, 'estoque' => 18],
        ];
    }
}
