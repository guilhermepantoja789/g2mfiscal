<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Services\Erp\ModuloDashboardService;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    public function dashboard(ModuloDashboardService $dashboards)
    {
        $stats = $dashboards->produtos((int) session('empresa_ativa'));

        return view('produtos.dashboard', compact('stats'));
    }

    public function index(Request $request)
    {
        $query = Produto::where('empresa_id', session('empresa_ativa'));

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('descricao', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('ean', 'like', "%{$term}%");
            });
        }

        $produtos = $query->orderBy('descricao')->paginate(15);

        return view('produtos.index', compact('produtos'));
    }

    public function create()
    {
        return view('produtos.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'descricao' => 'required|string|max:255',
            'sku' => 'nullable|string|max:60',
            'ean' => 'nullable|string|max:14',
            'ncm' => 'nullable|string|max:8',
            'cfop' => 'required|string|max:4',
            'csosn' => 'required|string|max:3',
            'unidade' => 'required|string|max:6',
            'preco_venda' => 'required|numeric|min:0',
            'custo_medio' => 'nullable|numeric|min:0',
            'estoque_atual' => 'nullable|numeric',
            'controla_estoque' => 'nullable|boolean',
            'ativo' => 'nullable|boolean',
        ]);

        Produto::create([
            'empresa_id' => session('empresa_ativa'),
            'descricao' => $validated['descricao'],
            'sku' => $validated['sku'] ?? null,
            'ean' => isset($validated['ean']) ? preg_replace('/\D/', '', $validated['ean']) : null,
            'ncm' => isset($validated['ncm']) ? preg_replace('/\D/', '', $validated['ncm']) : null,
            'cfop' => $validated['cfop'],
            'csosn' => $validated['csosn'],
            'unidade' => $validated['unidade'],
            'preco_venda' => $validated['preco_venda'],
            'custo_medio' => $validated['custo_medio'] ?? 0,
            'estoque_atual' => $validated['estoque_atual'] ?? 0,
            'controla_estoque' => $request->boolean('controla_estoque', true),
            'ativo' => $request->boolean('ativo', true),
        ]);

        return redirect()->route('produtos.index')->with('success', 'Produto cadastrado.');
    }

    public function edit(Produto $produto)
    {
        $this->authorizeEmpresa($produto->empresa_id);

        return view('produtos.edit', compact('produto'));
    }

    public function update(Request $request, Produto $produto)
    {
        $this->authorizeEmpresa($produto->empresa_id);

        $validated = $request->validate([
            'descricao' => 'required|string|max:255',
            'sku' => 'nullable|string|max:60',
            'ean' => 'nullable|string|max:14',
            'ncm' => 'nullable|string|max:8',
            'cfop' => 'required|string|max:4',
            'csosn' => 'required|string|max:3',
            'unidade' => 'required|string|max:6',
            'preco_venda' => 'required|numeric|min:0',
            'custo_medio' => 'nullable|numeric|min:0',
            'controla_estoque' => 'nullable|boolean',
            'ativo' => 'nullable|boolean',
        ]);

        $produto->update([
            'descricao' => $validated['descricao'],
            'sku' => $validated['sku'] ?? null,
            'ean' => isset($validated['ean']) ? preg_replace('/\D/', '', $validated['ean']) : null,
            'ncm' => isset($validated['ncm']) ? preg_replace('/\D/', '', $validated['ncm']) : null,
            'cfop' => $validated['cfop'],
            'csosn' => $validated['csosn'],
            'unidade' => $validated['unidade'],
            'preco_venda' => $validated['preco_venda'],
            'custo_medio' => $validated['custo_medio'] ?? $produto->custo_medio,
            'controla_estoque' => $request->boolean('controla_estoque', true),
            'ativo' => $request->boolean('ativo', true),
        ]);

        return redirect()->route('produtos.index')->with('success', 'Produto atualizado.');
    }

    public function destroy(Produto $produto)
    {
        $this->authorizeEmpresa($produto->empresa_id);
        $produto->delete();

        return redirect()->route('produtos.index')->with('success', 'Produto excluído.');
    }

    private function authorizeEmpresa(int $empresaId): void
    {
        if ($empresaId != session('empresa_ativa')) {
            abort(403);
        }
    }
}
