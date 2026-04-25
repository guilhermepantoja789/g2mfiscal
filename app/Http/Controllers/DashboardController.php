<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empresa;
use App\Models\NotaFiscal;
use App\Models\Cliente; // Novo
use App\Models\Servico; // Novo
use App\Models\DasPagamento; // Novo
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Verifica Sessão
        if (!session()->has('empresa_ativa')) {
            return redirect()->route('empresas.selecao');
        }

        $sessao = session('empresa_ativa');
        $empresaId = is_numeric($sessao) ? $sessao : data_get($sessao, 'id');

        $empresa = Empresa::find($empresaId);
        if (!$empresa) {
            session()->forget('empresa_ativa');
            return redirect()->route('empresas.selecao');
        }

        // 2. Listas para os Filtros (Dropdowns)
        $filtroClientes = Cliente::where('empresa_id', $empresaId)->orderBy('razao_social')->get();
        $filtroServicos = Servico::where('empresa_id', $empresaId)->orderBy('nome')->get();

        // 3. Define Datas (Padrão: Mês Atual)
        $dataInicio = $request->filled('data_inicio')
            ? Carbon::parse($request->data_inicio)->startOfDay()
            : Carbon::now()->startOfMonth();

        $dataFim = $request->filled('data_fim')
            ? Carbon::parse($request->data_fim)->endOfDay()
            : Carbon::now()->endOfMonth();

        // 4. Query Base (Aplica todos os filtros aqui)
        $baseQuery = NotaFiscal::where('empresa_id', $empresaId)
            ->where('status', 'autorizada') // Foca no realizado
            ->whereBetween('created_at', [$dataInicio, $dataFim]);

        // -> Filtro Dinâmico: Cliente
        if ($request->filled('cliente_id')) {
            $baseQuery->where('cliente_id', $request->cliente_id);
        }

        // -> Filtro Dinâmico: Serviço
        if ($request->filled('servico_id')) {
            $baseQuery->where('servico_id', $request->servico_id);
        }

        // 5. Clonagem para Estatísticas
        $faturamentoPeriodo = (clone $baseQuery)->sum('valor_servico');
        $totalNotas = (clone $baseQuery)->count();

        // Impostos
        $impostosStats = (clone $baseQuery)
            ->select(
                DB::raw('sum(v_tot_trib_fed) as total_federal'),
                DB::raw('sum(v_tot_trib_mun) as total_municipal'),
                DB::raw('sum(v_tot_trib_est) as total_estadual')
            )
            ->first();

        $impostosFederais = $impostosStats->total_federal ?? 0;
        $impostosMunicipais = $impostosStats->total_municipal ?? 0;
        $impostosTotal = $impostosFederais + $impostosMunicipais + ($impostosStats->total_estadual ?? 0);

        // Top Clientes (Se já filtrou por cliente, vai mostrar só ele mesmo, o que é esperado)
        $topClientes = (clone $baseQuery)
            ->select('cliente_id', DB::raw('sum(valor_servico) as total_gasto'), DB::raw('count(*) as qtd_notas'))
            ->groupBy('cliente_id')
            ->orderByDesc('total_gasto')
            ->with('cliente')
            ->take(5)
            ->get();

        // 6. Gráfico Inteligente
        $diasDiferenca = $dataInicio->diffInDays($dataFim);
        $agrupamento = $diasDiferenca <= 60 ? 'DIA' : 'MES';

        if ($agrupamento == 'DIA') {
            $historico = (clone $baseQuery)
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as data_label'),
                    DB::raw('sum(valor_servico) as total')
                )
                ->groupBy('data_label')
                ->orderBy('data_label')
                ->get();
            $graficoLabels = $historico->map(fn($item) => Carbon::createFromFormat('Y-m-d', $item->data_label)->format('d/m'));
        } else {
            $historico = (clone $baseQuery)
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as data_label'),
                    DB::raw('sum(valor_servico) as total')
                )
                ->groupBy('data_label')
                ->orderBy('data_label')
                ->get();
            $graficoLabels = $historico->map(fn($item) => Carbon::createFromFormat('Y-m', $item->data_label)->format('M/Y'));
        }

        $graficoValores = $historico->pluck('total');

        $stats = [
            'faturamento' => $faturamentoPeriodo,
            'notas_emitidas' => $totalNotas,
            'impostos_total' => $impostosTotal,
            'impostos_federais' => $impostosFederais,
            'impostos_municipais' => $impostosMunicipais,
            'grafico_labels' => $graficoLabels,
            'grafico_valores' => $graficoValores,
            'agrupamento' => $agrupamento == 'DIA' ? 'Diária' : 'Mensal'
        ];

        // 7. Estatísticas FINANCEIRAS (Novo Bloco)
        // Reutilizamos $baseQuery mas focamos na tabela cobrancas vinculada ou query direta
        // Para ser mais preciso, vamos fazer uma query direta na tabela Cobranca respeitando as datas
        // O Dashboard financeiro geralmente olha VENCIMENTO ou PAGAMENTO, não EMISSAO da nota.
        // Vamos assumir VENCIMENTO dentro do período selecionado.

        $financeiroStats = \App\Models\Cobranca::where('empresa_id', $empresaId)
            ->whereBetween('vencimento', [$dataInicio, $dataFim])
            ->select(
                DB::raw("SUM(CASE WHEN status = 'PENDING' THEN valor ELSE 0 END) as pendente"),
                DB::raw("SUM(CASE WHEN status = 'RECEIVED' THEN valor ELSE 0 END) as realizado"),
                DB::raw("SUM(CASE WHEN status = 'OVERDUE' OR (status='PENDING' AND vencimento < CURDATE()) THEN valor ELSE 0 END) as vencido")
            )
            ->first();

        // Adicionamos ao array $stats que vai pra view
        $stats['fin_pendente'] = $financeiroStats->pendente ?? 0;
        $stats['fin_realizado'] = $financeiroStats->realizado ?? 0;
        $stats['fin_vencido'] = $financeiroStats->vencido ?? 0;

        // 8. ESTATÍSTICAS DO DAS (Simples Nacional)
        // Usar data de emissão para o DAS do mês selecionado
        $competenciaDas = $dataInicio->format('Y-m');

        // Calcula imposto estimado (soma de (valor_servico * aliquota_iss / 100)) para notas não retidas deste mês
        $valorEstimadoDas = \App\Models\NotaFiscal::where('empresa_id', $empresaId)
            ->where('status', 'autorizada')
            ->where('tp_ret_issqn', 1)
            ->whereBetween('emissao', [$dataInicio->copy()->startOfMonth(), $dataInicio->copy()->endOfMonth()])
            ->sum(DB::raw('valor_servico * (aliquota_iss / 100)'));

        // Busca ou cria o registro pendente para a competência selecionada
        $dasAtual = DasPagamento::firstOrCreate(
            ['empresa_id' => $empresaId, 'competencia' => $competenciaDas],
            ['valor_estimado' => 0, 'status' => 'pendente']
        );

        // Atualiza o valor estimado se ainda estiver pendente e o valor mudou
        if ($dasAtual->status === 'pendente' && abs($dasAtual->valor_estimado - $valorEstimadoDas) > 0.01) {
            $dasAtual->update(['valor_estimado' => $valorEstimadoDas]);
        }

        // Calcula a provisão total acumulada (soma de todos os DAS pendentes até o mês selecionado)
        $provisaoDasTotal = DasPagamento::where('empresa_id', $empresaId)
            ->where('status', 'pendente')
            ->where('competencia', '<=', $competenciaDas)
            ->sum('valor_estimado');

        // Busca histórico de DAS para a listagem (últimos 6 meses, por exemplo)
        $historicoDas = DasPagamento::where('empresa_id', $empresaId)
            ->orderBy('competencia', 'desc')
            ->take(6)
            ->get();

        return view('dashboard', compact('empresa', 'stats', 'topClientes', 'filtroClientes', 'filtroServicos', 'dasAtual', 'provisaoDasTotal', 'historicoDas'));
    }
}
