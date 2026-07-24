<?php

namespace App\Services\Erp;

use App\Models\Empresa;
use App\Models\FormaPagamento;
use Carbon\Carbon;

class FormaPagamentoService
{
    /**
     * Garante formas padrão da empresa (idempotente).
     *
     * @return list<FormaPagamento>
     */
    public function garantirDefaults(Empresa $empresa): array
    {
        if (FormaPagamento::where('empresa_id', $empresa->id)->exists()) {
            return FormaPagamento::where('empresa_id', $empresa->id)
                ->orderBy('nome')
                ->get()
                ->all();
        }

        $defaults = [
            [
                'codigo' => '01',
                'nome' => 'Dinheiro',
                'tipo_liquidacao' => FormaPagamento::TIPO_AVISTA,
                'dias_recebimento' => 0,
                'parcelas' => 1,
                'juros_percentual' => 0,
            ],
            [
                'codigo' => '17',
                'nome' => 'PIX',
                'tipo_liquidacao' => FormaPagamento::TIPO_AVISTA,
                'dias_recebimento' => 0,
                'parcelas' => 1,
                'juros_percentual' => 0,
            ],
            [
                'codigo' => '04',
                'nome' => 'Cartão débito',
                'tipo_liquidacao' => FormaPagamento::TIPO_AVISTA,
                'dias_recebimento' => 0,
                'parcelas' => 1,
                'juros_percentual' => 0,
            ],
            [
                'codigo' => '03',
                'nome' => 'Cartão crédito',
                'tipo_liquidacao' => FormaPagamento::TIPO_PRAZO,
                'dias_recebimento' => 30,
                'parcelas' => 1,
                'juros_percentual' => 0,
            ],
        ];

        $created = [];
        foreach ($defaults as $row) {
            $created[] = FormaPagamento::create([
                'empresa_id' => $empresa->id,
                'ativo' => true,
                'gera_lancamento' => true,
                ...$row,
            ]);
        }

        return $created;
    }

    /**
     * Calcula parcelas iguais (última absorve centavos) e vencimentos D+N, D+2N…
     *
     * @return list<array{parcela: int, total_parcelas: int, valor: float, vencimento: string, pago_avista: bool}>
     */
    public function calcularParcelas(FormaPagamento $forma, float $valorBase, ?Carbon $referencia = null): array
    {
        $referencia = $referencia?->copy()->startOfDay() ?? now()->startOfDay();
        $total = $forma->valorComJuros($valorBase);
        $n = max(1, (int) $forma->parcelas);
        $avista = $forma->isAvista();
        $dias = max(0, (int) $forma->dias_recebimento);

        if ($n === 1) {
            $venc = $avista
                ? $referencia->toDateString()
                : $referencia->copy()->addDays($dias)->toDateString();

            return [[
                'parcela' => 1,
                'total_parcelas' => 1,
                'valor' => $total,
                'vencimento' => $venc,
                'pago_avista' => $avista,
            ]];
        }

        $centavos = (int) round($total * 100);
        $base = intdiv($centavos, $n);
        $resto = $centavos % $n;
        $parcelas = [];

        for ($i = 1; $i <= $n; $i++) {
            $valorCentavos = $base + ($i === $n ? $resto : 0);
            $offsetDias = $dias * $i;
            $parcelas[] = [
                'parcela' => $i,
                'total_parcelas' => $n,
                'valor' => round($valorCentavos / 100, 2),
                'vencimento' => $referencia->copy()->addDays($offsetDias)->toDateString(),
                'pago_avista' => false,
            ];
        }

        return $parcelas;
    }
}
