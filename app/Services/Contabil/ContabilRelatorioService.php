<?php

namespace App\Services\Contabil;

use App\Models\LancamentoContabil;
use App\Models\LancamentoContabilItem;
use Carbon\Carbon;

class ContabilRelatorioService
{
    /**
     * @return array{
     *   receitas: float,
     *   despesas: float,
     *   resultado: float,
     *   linhas: list<array{conta_id: int, codigo: string, nome: string, tipo: string, valor: float}>
     * }
     */
    public function dre(int $empresaId, Carbon $inicio, Carbon $fim): array
    {
        $itens = $this->itensPeriodo($empresaId, $inicio, $fim)
            ->filter(fn (LancamentoContabilItem $i) => in_array($i->conta?->tipo, ['receita', 'despesa'], true));

        $linhas = [];
        $receitas = 0.0;
        $despesas = 0.0;

        foreach ($itens->groupBy('conta_id') as $contaId => $group) {
            $conta = $group->first()->conta;
            if (! $conta) {
                continue;
            }

            $creditos = (float) $group->where('tipo', 'C')->sum('valor');
            $debitos = (float) $group->where('tipo', 'D')->sum('valor');

            // Receita: natureza credora → saldo = C - D; Despesa: natureza devedora → D - C
            $valor = $conta->tipo === 'receita'
                ? round($creditos - $debitos, 2)
                : round($debitos - $creditos, 2);

            if (abs($valor) < 0.005) {
                continue;
            }

            if ($conta->tipo === 'receita') {
                $receitas += $valor;
            } else {
                $despesas += $valor;
            }

            $linhas[] = [
                'conta_id' => (int) $contaId,
                'codigo' => $conta->codigo,
                'nome' => $conta->nome,
                'tipo' => $conta->tipo,
                'valor' => $valor,
            ];
        }

        usort($linhas, fn ($a, $b) => strcmp($a['codigo'], $b['codigo']));

        return [
            'receitas' => round($receitas, 2),
            'despesas' => round($despesas, 2),
            'resultado' => round($receitas - $despesas, 2),
            'linhas' => $linhas,
        ];
    }

    /**
     * Balanço gerencial acumulado até a data (ativo / passivo / patrimônio).
     *
     * @return array{
     *   ativo: float,
     *   passivo: float,
     *   patrimonio: float,
     *   linhas: list<array{conta_id: int, codigo: string, nome: string, tipo: string, saldo: float}>
     * }
     */
    public function balanco(int $empresaId, Carbon $ate): array
    {
        $itens = $this->itensAte($empresaId, $ate)
            ->filter(fn (LancamentoContabilItem $i) => in_array($i->conta?->tipo, ['ativo', 'passivo', 'patrimonio'], true));

        $linhas = [];
        $ativo = 0.0;
        $passivo = 0.0;
        $patrimonio = 0.0;

        foreach ($itens->groupBy('conta_id') as $contaId => $group) {
            $conta = $group->first()->conta;
            if (! $conta) {
                continue;
            }

            $creditos = (float) $group->where('tipo', 'C')->sum('valor');
            $debitos = (float) $group->where('tipo', 'D')->sum('valor');

            // Ativo (D): D - C; Passivo/Patrimônio (C): C - D
            $saldo = in_array($conta->tipo, ['passivo', 'patrimonio'], true)
                ? round($creditos - $debitos, 2)
                : round($debitos - $creditos, 2);

            if (abs($saldo) < 0.005) {
                continue;
            }

            match ($conta->tipo) {
                'ativo' => $ativo += $saldo,
                'passivo' => $passivo += $saldo,
                'patrimonio' => $patrimonio += $saldo,
                default => null,
            };

            $linhas[] = [
                'conta_id' => (int) $contaId,
                'codigo' => $conta->codigo,
                'nome' => $conta->nome,
                'tipo' => $conta->tipo,
                'saldo' => $saldo,
            ];
        }

        usort($linhas, fn ($a, $b) => strcmp($a['codigo'], $b['codigo']));

        return [
            'ativo' => round($ativo, 2),
            'passivo' => round($passivo, 2),
            'patrimonio' => round($patrimonio, 2),
            'linhas' => $linhas,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, LancamentoContabilItem>
     */
    private function itensPeriodo(int $empresaId, Carbon $inicio, Carbon $fim)
    {
        return LancamentoContabilItem::query()
            ->whereHas('lancamento', function ($q) use ($empresaId, $inicio, $fim) {
                $q->where('empresa_id', $empresaId)
                    ->where('status', LancamentoContabil::STATUS_LANCADO)
                    ->whereDate('data', '>=', $inicio->toDateString())
                    ->whereDate('data', '<=', $fim->toDateString());
            })
            ->with('conta')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, LancamentoContabilItem>
     */
    private function itensAte(int $empresaId, Carbon $ate)
    {
        return LancamentoContabilItem::query()
            ->whereHas('lancamento', function ($q) use ($empresaId, $ate) {
                $q->where('empresa_id', $empresaId)
                    ->where('status', LancamentoContabil::STATUS_LANCADO)
                    ->whereDate('data', '<=', $ate->toDateString());
            })
            ->with('conta')
            ->get();
    }
}
