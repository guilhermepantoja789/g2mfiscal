<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empresa;
use App\Models\NotaFiscal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Verifica se existe sessão
        if (!session()->has('empresa_ativa')) {
            return redirect()->route('empresas.selecao');
        }

        // --- CORREÇÃO PRINCIPAL AQUI ---
        // Recupera o objeto da sessão
        $empresaSessao = session('empresa_ativa');

        // Se por acaso vier como array ou objeto, extraímos o ID
        $empresaId = is_object($empresaSessao) ? $empresaSessao->id : (is_array($empresaSessao) ? $empresaSessao['id'] : $empresaSessao);

        // Busca o objeto fresco do banco para garantir dados atualizados (opcional mas recomendado)
        $empresa = Empresa::find($empresaId);

        if (!$empresa) {
            // Se o ID da sessão não existir mais no banco
            session()->forget('empresa_ativa');
            return redirect()->route('empresas.selecao');
        }
        // -------------------------------

        $inicioMes = Carbon::now()->startOfMonth();
        $fimMes = Carbon::now()->endOfMonth();

        // --- CARDS PRINCIPAIS ---
        $faturamentoMes = NotaFiscal::where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$inicioMes, $fimMes])
            ->where('status', '!=', 'cancelada')
            ->sum('valor_servico');

        $totalNotas = NotaFiscal::where('empresa_id', $empresaId)->count();

        $impostosMes = NotaFiscal::where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$inicioMes, $fimMes])
            ->where('status', '!=', 'cancelada')
            ->sum('valor_iss');


        // --- TOP CLIENTES (Ranking) ---
        $topClientes = NotaFiscal::where('empresa_id', $empresaId)
            ->where('status', 'autorizada')
            ->select('cliente_id', DB::raw('sum(valor_servico) as total_gasto'), DB::raw('count(*) as qtd_notas'))
            ->groupBy('cliente_id')
            ->orderByDesc('total_gasto')
            ->with('cliente')
            ->take(5)
            ->get();


        // --- DADOS PARA O GRÁFICO (Evolução Mensal) ---
        $historico = NotaFiscal::where('empresa_id', $empresaId)
            ->where('created_at', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->where('status', 'autorizada')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mes'),
                DB::raw('sum(valor_servico) as total')
            )
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $graficoLabels = $historico->map(fn($item) => Carbon::createFromFormat('Y-m', $item->mes)->format('M/Y'));
        $graficoValores = $historico->pluck('total');

        $stats = [
            'faturamento_mes' => $faturamentoMes,
            'notas_emitidas' => $totalNotas,
            'impostos_mes' => $impostosMes,
            'grafico_labels' => $graficoLabels,
            'grafico_valores' => $graficoValores
        ];

        return view('dashboard', compact('empresa', 'stats', 'topClientes'));
    }
}
