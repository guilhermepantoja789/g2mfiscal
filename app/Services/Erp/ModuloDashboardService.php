<?php

namespace App\Services\Erp;

use App\Models\DocumentoComercial;
use App\Models\EstoqueMovimentacao;
use App\Models\FormaPagamento;
use App\Models\Fornecedor;
use App\Models\LancamentoFinanceiro;
use App\Models\Nfce;
use App\Models\Produto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
class ModuloDashboardService
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolvePeriod(Request $request): array
    {
        $inicio = $request->filled('data_inicio')
            ? Carbon::parse($request->data_inicio)->startOfDay()
            : Carbon::now()->startOfMonth();

        $fim = $request->filled('data_fim')
            ? Carbon::parse($request->data_fim)->endOfDay()
            : Carbon::now()->endOfMonth();

        return [$inicio, $fim];
    }

    public function financeiro(int $empresaId, Carbon $inicio, Carbon $fim): array
    {
        $base = LancamentoFinanceiro::query()->where('empresa_id', $empresaId);
        $hoje = Carbon::today();

        $aReceberAberto = (float) (clone $base)
            ->where('tipo', LancamentoFinanceiro::TIPO_RECEBER)
            ->where('status', LancamentoFinanceiro::STATUS_ABERTO)
            ->sum('valor');

        $aPagarAberto = (float) (clone $base)
            ->where('tipo', LancamentoFinanceiro::TIPO_PAGAR)
            ->where('status', LancamentoFinanceiro::STATUS_ABERTO)
            ->sum('valor');

        $vencidosReceber = (float) (clone $base)
            ->where('tipo', LancamentoFinanceiro::TIPO_RECEBER)
            ->where('status', LancamentoFinanceiro::STATUS_ABERTO)
            ->whereDate('vencimento', '<', $hoje)
            ->sum('valor');

        $vencidosPagar = (float) (clone $base)
            ->where('tipo', LancamentoFinanceiro::TIPO_PAGAR)
            ->where('status', LancamentoFinanceiro::STATUS_ABERTO)
            ->whereDate('vencimento', '<', $hoje)
            ->sum('valor');

        $recebidoPeriodo = (float) (clone $base)
            ->where('tipo', LancamentoFinanceiro::TIPO_RECEBER)
            ->where('status', LancamentoFinanceiro::STATUS_PAGO)
            ->whereBetween('pago_em', [$inicio, $fim])
            ->sum('valor');

        $pagoPeriodo = (float) (clone $base)
            ->where('tipo', LancamentoFinanceiro::TIPO_PAGAR)
            ->where('status', LancamentoFinanceiro::STATUS_PAGO)
            ->whereBetween('pago_em', [$inicio, $fim])
            ->sum('valor');

        $abertos = (clone $base)
            ->where('status', LancamentoFinanceiro::STATUS_ABERTO)
            ->whereNotNull('vencimento')
            ->get(['valor', 'vencimento']);

        $aging = ['0_7' => 0.0, '8_30' => 0.0, '30_mais' => 0.0];
        foreach ($abertos as $lancamento) {
            $atraso = $lancamento->vencimento->gte($hoje)
                ? 0
                : (int) $lancamento->vencimento->diffInDays($hoje);
            if ($atraso <= 7) {
                $aging['0_7'] += (float) $lancamento->valor;
            } elseif ($atraso <= 30) {
                $aging['8_30'] += (float) $lancamento->valor;
            } else {
                $aging['30_mais'] += (float) $lancamento->valor;
            }
        }

        $baixasPorDia = (clone $base)
            ->where('status', LancamentoFinanceiro::STATUS_PAGO)
            ->whereBetween('pago_em', [$inicio, $fim])
            ->selectRaw('DATE(pago_em) as dia, SUM(valor) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->pluck('total', 'dia');

        [$graficoLabels, $graficoValores] = $this->fillDailySeries($inicio, $fim, $baixasPorDia);

        $porFormaRaw = (clone $base)
            ->where('status', LancamentoFinanceiro::STATUS_PAGO)
            ->whereBetween('pago_em', [$inicio, $fim])
            ->selectRaw('forma_pagamento_id, COUNT(*) as qtd, SUM(valor) as total')
            ->groupBy('forma_pagamento_id')
            ->get();

        $nomesForma = FormaPagamento::query()
            ->whereIn('id', $porFormaRaw->pluck('forma_pagamento_id')->filter()->unique())
            ->pluck('nome', 'id');

        $porForma = $porFormaRaw->map(fn ($linha) => [
            'nome' => $nomesForma[$linha->forma_pagamento_id] ?? 'Sem forma',
            'qtd' => (int) $linha->qtd,
            'total' => (float) $linha->total,
        ])->values()->all();

        $proximosVencimentos = (clone $base)
            ->where('status', LancamentoFinanceiro::STATUS_ABERTO)
            ->whereNotNull('vencimento')
            ->with(['cliente', 'fornecedor'])
            ->orderBy('vencimento')
            ->limit(10)
            ->get();

        return [
            'a_receber_aberto' => $aReceberAberto,
            'a_pagar_aberto' => $aPagarAberto,
            'vencidos_receber' => $vencidosReceber,
            'vencidos_pagar' => $vencidosPagar,
            'vencidos_total' => $vencidosReceber + $vencidosPagar,
            'recebido_periodo' => $recebidoPeriodo,
            'pago_periodo' => $pagoPeriodo,
            'aging' => $aging,
            'grafico_labels' => $graficoLabels,
            'grafico_valores' => $graficoValores,
            'por_forma' => $porForma,
            'proximos_vencimentos' => $proximosVencimentos,
        ];
    }

    public function estoque(int $empresaId, Carbon $inicio, Carbon $fim): array
    {
        $produtos = Produto::query()
            ->where('empresa_id', $empresaId)
            ->where('controla_estoque', true);

        $valorEstoque = (float) (clone $produtos)
            ->selectRaw('COALESCE(SUM(estoque_atual * custo_medio), 0) as total')
            ->value('total');

        $comSaldo = (clone $produtos)->where('estoque_atual', '>', 0)->count();
        $zerados = (clone $produtos)->where('estoque_atual', '=', 0)->count();
        $negativos = (clone $produtos)->where('estoque_atual', '<', 0)->count();

        $movBase = EstoqueMovimentacao::query()
            ->where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$inicio, $fim]);

        $entradas = (float) (clone $movBase)
            ->where('tipo', EstoqueMovimentacao::TIPO_ENTRADA)
            ->sum('quantidade');

        $saidas = (float) (clone $movBase)
            ->where('tipo', EstoqueMovimentacao::TIPO_SAIDA)
            ->sum('quantidade');

        $topSaidasRaw = EstoqueMovimentacao::query()
            ->where('empresa_id', $empresaId)
            ->where('tipo', EstoqueMovimentacao::TIPO_SAIDA)
            ->whereBetween('created_at', [$inicio, $fim])
            ->selectRaw('produto_id, SUM(quantidade) as total_qtd')
            ->groupBy('produto_id')
            ->orderByDesc('total_qtd')
            ->limit(5)
            ->get();

        $produtosMap = Produto::query()
            ->whereIn('id', $topSaidasRaw->pluck('produto_id'))
            ->get()
            ->keyBy('id');

        $topSaidas = $topSaidasRaw->map(function ($row) use ($produtosMap) {
            return (object) [
                'produto_id' => $row->produto_id,
                'total_qtd' => (float) $row->total_qtd,
                'produto' => $produtosMap->get($row->produto_id),
            ];
        });

        $criticos = Produto::query()
            ->where('empresa_id', $empresaId)
            ->where('controla_estoque', true)
            ->orderBy('estoque_atual')
            ->limit(10)
            ->get();

        return [
            'valor_estoque' => $valorEstoque,
            'com_saldo' => $comSaldo,
            'zerados' => $zerados,
            'negativos' => $negativos,
            'entradas' => $entradas,
            'saidas' => $saidas,
            'top_saidas' => $topSaidas,
            'criticos' => $criticos,
        ];
    }

    public function documentos(int $empresaId, Carbon $inicio, Carbon $fim): array
    {
        $base = DocumentoComercial::query()
            ->where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$inicio, $fim]);

        $faturamento = (float) (clone $base)
            ->where('tipo', DocumentoComercial::TIPO_VENDA)
            ->where('status', DocumentoComercial::STATUS_AUTORIZADO)
            ->sum('valor_total');

        $compras = (float) (clone $base)
            ->where('tipo', DocumentoComercial::TIPO_COMPRA)
            ->where('status', DocumentoComercial::STATUS_AUTORIZADO)
            ->sum('valor_total');

        $funil = (clone $base)
            ->selectRaw('status, COUNT(*) as qtd')
            ->groupBy('status')
            ->pluck('qtd', 'status')
            ->all();

        $porCanal = (clone $base)
            ->where('status', DocumentoComercial::STATUS_AUTORIZADO)
            ->selectRaw('canal_fiscal, COUNT(*) as qtd, SUM(valor_total) as total')
            ->groupBy('canal_fiscal')
            ->get()
            ->map(fn ($row) => [
                'canal' => $row->canal_fiscal ?: '—',
                'qtd' => (int) $row->qtd,
                'total' => (float) $row->total,
            ])
            ->values()
            ->all();

        $porFormaRaw = (clone $base)
            ->where('status', DocumentoComercial::STATUS_AUTORIZADO)
            ->selectRaw('forma_pagamento_id, forma_pagamento, COUNT(*) as qtd, SUM(valor_total) as total')
            ->groupBy('forma_pagamento_id', 'forma_pagamento')
            ->get();

        $nomesForma = FormaPagamento::query()
            ->whereIn('id', $porFormaRaw->pluck('forma_pagamento_id')->filter()->unique())
            ->pluck('nome', 'id');

        $porForma = $porFormaRaw->map(fn ($linha) => [
            'nome' => $nomesForma[$linha->forma_pagamento_id] ?? ($linha->forma_pagamento ?: '—'),
            'qtd' => (int) $linha->qtd,
            'total' => (float) $linha->total,
        ])->values()->all();

        $serieRaw = (clone $base)
            ->where('tipo', DocumentoComercial::TIPO_VENDA)
            ->where('status', DocumentoComercial::STATUS_AUTORIZADO)
            ->selectRaw('DATE(created_at) as dia, SUM(valor_total) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->pluck('total', 'dia');

        [$graficoLabels, $graficoValores] = $this->fillDailySeries($inicio, $fim, $serieRaw);

        $recentes = DocumentoComercial::query()
            ->where('empresa_id', $empresaId)
            ->with(['cliente', 'fornecedor', 'formaPagamentoRel'])
            ->latest()
            ->limit(8)
            ->get();

        return [
            'faturamento' => $faturamento,
            'compras' => $compras,
            'funil' => $funil,
            'por_canal' => $porCanal,
            'por_forma' => $porForma,
            'grafico_labels' => $graficoLabels,
            'grafico_valores' => $graficoValores,
            'recentes' => $recentes,
            'qtd_periodo' => (clone $base)->count(),
        ];
    }

    public function nfce(int $empresaId, Carbon $inicio, Carbon $fim): array
    {
        $base = Nfce::query()
            ->where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$inicio, $fim]);

        $autorizadasQtd = (clone $base)->where('status', 'autorizada')->count();
        $autorizadasValor = (float) (clone $base)->where('status', 'autorizada')->sum('valor_total');
        $canceladas = (clone $base)->where('status', 'cancelada')->count();
        $pendentes = (clone $base)->where('status', 'pendente_transmissao')->count();
        $erros = (clone $base)->whereIn('status', ['erro', 'rejeitada'])->count();
        $contingencia = (clone $base)->where('tp_emis', '!=', 1)->count();

        $serieRaw = (clone $base)
            ->where('status', 'autorizada')
            ->selectRaw('DATE(created_at) as dia, SUM(valor_total) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->pluck('total', 'dia');

        [$graficoLabels, $graficoValores] = $this->fillDailySeries($inicio, $fim, $serieRaw);

        $recentes = Nfce::query()
            ->where('empresa_id', $empresaId)
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return [
            'autorizadas_qtd' => $autorizadasQtd,
            'autorizadas_valor' => $autorizadasValor,
            'canceladas' => $canceladas,
            'pendentes' => $pendentes,
            'erros' => $erros,
            'contingencia' => $contingencia,
            'grafico_labels' => $graficoLabels,
            'grafico_valores' => $graficoValores,
            'recentes' => $recentes,
        ];
    }

    public function produtos(int $empresaId): array
    {
        $base = Produto::query()->where('empresa_id', $empresaId);

        $total = (clone $base)->count();
        $ativos = (clone $base)->where('ativo', true)->count();
        $inativos = (clone $base)->where('ativo', false)->count();
        $comEstoque = (clone $base)->where('controla_estoque', true)->count();

        $valorPotencial = (float) (clone $base)
            ->where('controla_estoque', true)
            ->selectRaw('COALESCE(SUM(estoque_atual * preco_venda), 0) as total')
            ->value('total');

        $margemMedia = (float) (clone $base)
            ->where('ativo', true)
            ->where('preco_venda', '>', 0)
            ->selectRaw('COALESCE(AVG(preco_venda - custo_medio), 0) as margem')
            ->value('margem');

        $semPreco = (clone $base)->where(function ($q) {
            $q->whereNull('preco_venda')->orWhere('preco_venda', '<=', 0);
        })->count();

        $semNcm = (clone $base)->where(function ($q) {
            $q->whereNull('ncm')->orWhere('ncm', '');
        })->count();

        $topPreco = (clone $base)
            ->where('ativo', true)
            ->orderByDesc('preco_venda')
            ->limit(5)
            ->get();

        $preview = (clone $base)->orderBy('descricao')->limit(10)->get();

        return [
            'total' => $total,
            'ativos' => $ativos,
            'inativos' => $inativos,
            'com_estoque' => $comEstoque,
            'valor_potencial' => $valorPotencial,
            'margem_media' => $margemMedia,
            'sem_preco' => $semPreco,
            'sem_ncm' => $semNcm,
            'top_preco' => $topPreco,
            'preview' => $preview,
        ];
    }

    public function fornecedores(int $empresaId, Carbon $inicio, Carbon $fim, bool $incluirFinanceiro = false): array
    {
        $total = Fornecedor::query()->where('empresa_id', $empresaId)->count();

        $comprasRaw = DocumentoComercial::query()
            ->where('empresa_id', $empresaId)
            ->where('tipo', DocumentoComercial::TIPO_COMPRA)
            ->where('status', DocumentoComercial::STATUS_AUTORIZADO)
            ->whereBetween('created_at', [$inicio, $fim])
            ->whereNotNull('fornecedor_id')
            ->selectRaw('fornecedor_id, COUNT(*) as qtd, SUM(valor_total) as total')
            ->groupBy('fornecedor_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $fornecedoresMap = Fornecedor::query()
            ->whereIn('id', $comprasRaw->pluck('fornecedor_id'))
            ->get()
            ->keyBy('id');

        $comprasPorFornecedor = $comprasRaw->map(function ($row) use ($fornecedoresMap) {
            return (object) [
                'fornecedor_id' => $row->fornecedor_id,
                'qtd' => (int) $row->qtd,
                'total' => (float) $row->total,
                'fornecedor' => $fornecedoresMap->get($row->fornecedor_id),
            ];
        });

        $comprasTotal = (float) $comprasPorFornecedor->sum('total');

        $aPagarAberto = null;
        if ($incluirFinanceiro) {
            $aPagarAberto = (float) LancamentoFinanceiro::query()
                ->where('empresa_id', $empresaId)
                ->where('tipo', LancamentoFinanceiro::TIPO_PAGAR)
                ->where('status', LancamentoFinanceiro::STATUS_ABERTO)
                ->whereNotNull('fornecedor_id')
                ->sum('valor');
        }

        $preview = Fornecedor::query()
            ->where('empresa_id', $empresaId)
            ->latest()
            ->limit(10)
            ->get();

        return [
            'total' => $total,
            'compras_total' => $comprasTotal,
            'compras_por_fornecedor' => $comprasPorFornecedor,
            'a_pagar_aberto' => $aPagarAberto,
            'preview' => $preview,
        ];
    }

    /**
     * @param  Collection<string, mixed>|array<string, mixed>  $byDay
     * @return array{0: list<string>, 1: list<float>}
     */
    private function fillDailySeries(Carbon $inicio, Carbon $fim, Collection|array $byDay): array
    {
        $map = $byDay instanceof Collection ? $byDay->all() : $byDay;
        $labels = [];
        $valores = [];
        $cursor = $inicio->copy()->startOfDay();
        $limit = $fim->copy()->startOfDay();

        // Evita séries gigantes (anos)
        $dias = $cursor->diffInDays($limit) + 1;
        if ($dias > 93) {
            $cursor = $inicio->copy()->startOfMonth();
            while ($cursor->lte($limit)) {
                $key = $cursor->format('Y-m');
                $labels[] = $cursor->format('m/Y');
                $sum = 0.0;
                foreach ($map as $dia => $total) {
                    if (str_starts_with((string) $dia, $key)) {
                        $sum += (float) $total;
                    }
                }
                $valores[] = round($sum, 2);
                $cursor->addMonth();
            }

            return [$labels, $valores];
        }

        while ($cursor->lte($limit)) {
            $key = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('d/m');
            $valores[] = round((float) ($map[$key] ?? 0), 2);
            $cursor->addDay();
        }

        return [$labels, $valores];
    }
}
