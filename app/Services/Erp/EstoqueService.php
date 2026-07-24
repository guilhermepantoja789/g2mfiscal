<?php

namespace App\Services\Erp;

use App\Models\DocumentoComercial;
use App\Models\EstoqueMovimentacao;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EstoqueService
{
    public const ORIGEM_ESTORNO_CANCELAMENTO = 'estorno_cancelamento';

    public function entrada(
        Produto $produto,
        float $quantidade,
        float $custoUnitario = 0,
        ?int $documentoId = null,
        string $origem = 'manual',
        ?string $observacao = null,
    ): EstoqueMovimentacao {
        return $this->movimentar(
            $produto,
            EstoqueMovimentacao::TIPO_ENTRADA,
            abs($quantidade),
            $custoUnitario,
            $documentoId,
            $origem,
            $observacao,
        );
    }

    public function saida(
        Produto $produto,
        float $quantidade,
        float $custoUnitario = 0,
        ?int $documentoId = null,
        string $origem = 'manual',
        ?string $observacao = null,
    ): EstoqueMovimentacao {
        return $this->movimentar(
            $produto,
            EstoqueMovimentacao::TIPO_SAIDA,
            abs($quantidade),
            $custoUnitario,
            $documentoId,
            $origem,
            $observacao,
        );
    }

    public function movimentar(
        Produto $produto,
        string $tipo,
        float $quantidade,
        float $custoUnitario = 0,
        ?int $documentoId = null,
        string $origem = 'manual',
        ?string $observacao = null,
    ): EstoqueMovimentacao {
        if ($quantidade <= 0) {
            throw new RuntimeException('Quantidade do movimento deve ser maior que zero.');
        }

        return DB::transaction(function () use ($produto, $tipo, $quantidade, $custoUnitario, $documentoId, $origem, $observacao) {
            $produto = Produto::query()->whereKey($produto->id)->lockForUpdate()->firstOrFail();

            if (! $produto->controla_estoque) {
                $saldo = (float) $produto->estoque_atual;

                return EstoqueMovimentacao::create([
                    'empresa_id' => $produto->empresa_id,
                    'produto_id' => $produto->id,
                    'documento_comercial_id' => $documentoId,
                    'tipo' => $tipo,
                    'quantidade' => $quantidade,
                    'custo_unitario' => $custoUnitario,
                    'saldo_apos' => $saldo,
                    'origem' => $origem,
                    'observacao' => $observacao ?? 'Produto sem controle de estoque',
                ]);
            }

            $saldoAtual = (float) $produto->estoque_atual;
            $delta = $tipo === EstoqueMovimentacao::TIPO_SAIDA ? -$quantidade : $quantidade;

            if ($tipo === EstoqueMovimentacao::TIPO_AJUSTE) {
                $delta = $quantidade; // quantidade já com sinal
            }

            $novoSaldo = round($saldoAtual + $delta, 4);

            if ($novoSaldo < -0.0001) {
                throw new RuntimeException("Estoque insuficiente para o produto {$produto->descricao}.");
            }

            $produto->estoque_atual = max(0, $novoSaldo);

            if ($tipo === EstoqueMovimentacao::TIPO_ENTRADA && $custoUnitario > 0 && $quantidade > 0) {
                $estoqueAnterior = $saldoAtual;
                $custoAnterior = (float) $produto->custo_medio;
                $totalAnterior = $estoqueAnterior * $custoAnterior;
                $totalEntrada = $quantidade * $custoUnitario;
                $novoEstoque = $estoqueAnterior + $quantidade;
                if ($novoEstoque > 0) {
                    $produto->custo_medio = round(($totalAnterior + $totalEntrada) / $novoEstoque, 4);
                }
            }

            $produto->save();

            return EstoqueMovimentacao::create([
                'empresa_id' => $produto->empresa_id,
                'produto_id' => $produto->id,
                'documento_comercial_id' => $documentoId,
                'tipo' => $tipo === EstoqueMovimentacao::TIPO_AJUSTE
                    ? ($delta >= 0 ? EstoqueMovimentacao::TIPO_ENTRADA : EstoqueMovimentacao::TIPO_SAIDA)
                    : $tipo,
                'quantidade' => abs($quantidade),
                'custo_unitario' => $custoUnitario,
                'saldo_apos' => $produto->estoque_atual,
                'origem' => $origem,
                'observacao' => $observacao,
            ]);
        });
    }

    /**
     * Estorna movimentos de estoque do documento (ex.: cancelamento fiscal).
     * Idempotente: se já houver movimentos com origem de estorno, não gera de novo.
     *
     * @return list<EstoqueMovimentacao>
     */
    public function estornarPorDocumento(
        DocumentoComercial $documento,
        string $origemEstorno = self::ORIGEM_ESTORNO_CANCELAMENTO,
        ?string $observacao = null,
    ): array {
        $jaEstornados = EstoqueMovimentacao::query()
            ->where('documento_comercial_id', $documento->id)
            ->where('origem', $origemEstorno)
            ->orderBy('id')
            ->get();

        if ($jaEstornados->isNotEmpty()) {
            return $jaEstornados->all();
        }

        $originais = EstoqueMovimentacao::query()
            ->where('documento_comercial_id', $documento->id)
            ->where('origem', '!=', $origemEstorno)
            ->orderBy('id')
            ->get();

        if ($originais->isEmpty()) {
            return [];
        }

        $obs = $observacao ?? sprintf('Estorno cancelamento fiscal doc #%d', $documento->id);
        $criados = [];

        return DB::transaction(function () use ($originais, $documento, $origemEstorno, $obs, &$criados) {
            foreach ($originais as $mov) {
                $produto = Produto::query()->find($mov->produto_id);
                if (! $produto) {
                    continue;
                }

                $qty = (float) $mov->quantidade;
                $custo = (float) $mov->custo_unitario;

                if ($mov->tipo === EstoqueMovimentacao::TIPO_SAIDA) {
                    $criados[] = $this->entrada(
                        $produto,
                        $qty,
                        $custo,
                        $documento->id,
                        $origemEstorno,
                        $obs,
                    );
                } elseif ($mov->tipo === EstoqueMovimentacao::TIPO_ENTRADA) {
                    $criados[] = $this->saida(
                        $produto,
                        $qty,
                        $custo,
                        $documento->id,
                        $origemEstorno,
                        $obs,
                    );
                }
            }

            return $criados;
        });
    }
}
