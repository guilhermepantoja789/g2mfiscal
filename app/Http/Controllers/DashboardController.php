<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empresa;
use App\Models\NotaFiscal;
use App\Models\Cliente; // Novo
use App\Models\Servico; // Novo
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

        return view('dashboard', compact('empresa', 'stats', 'topClientes', 'filtroClientes', 'filtroServicos'));
    }
}
