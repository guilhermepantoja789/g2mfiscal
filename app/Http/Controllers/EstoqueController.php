<?php

namespace App\Http\Controllers;

use App\Models\EstoqueMovimentacao;
use App\Models\Produto;
use App\Services\Erp\ModuloDashboardService;
use Illuminate\Http\Request;

class EstoqueController extends Controller
{
    public function dashboard(Request $request, ModuloDashboardService $dashboards)
    {
        $empresaId = (int) session('empresa_ativa');
        [$inicio, $fim] = $dashboards->resolvePeriod($request);
        $stats = $dashboards->estoque($empresaId, $inicio, $fim);

        return view('estoque.dashboard', compact('stats', 'inicio', 'fim'));
    }

    public function index(Request $request)
    {
        $produtos = Produto::query()
            ->where('empresa_id', session('empresa_ativa'))
            ->where('controla_estoque', true)
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->search;
                $q->where(function ($inner) use ($term) {
                    $inner->where('descricao', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%");
                });
            })
            ->orderBy('descricao')
            ->paginate(20);

        return view('estoque.index', compact('produtos'));
    }

    public function show(int $produtoId)
    {
        $produto = Produto::query()
            ->where('empresa_id', session('empresa_ativa'))
            ->findOrFail($produtoId);

        $movimentacoes = EstoqueMovimentacao::query()
            ->where('empresa_id', session('empresa_ativa'))
            ->where('produto_id', $produto->id)
            ->with('documento')
            ->latest()
            ->paginate(30);

        return view('estoque.show', compact('produto', 'movimentacoes'));
    }
}
