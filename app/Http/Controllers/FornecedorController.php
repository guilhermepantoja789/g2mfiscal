<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\EmpresaModulo;
use App\Models\Fornecedor;
use App\Rules\CpfCnpj;
use App\Services\Erp\ModuloDashboardService;
use Illuminate\Http\Request;

class FornecedorController extends Controller
{
    public function dashboard(Request $request, ModuloDashboardService $dashboards)
    {
        $empresaId = (int) session('empresa_ativa');
        $empresa = Empresa::with('modulos')->find($empresaId);
        $incluirFinanceiro = $empresa
            ? $empresa->temModulo(EmpresaModulo::MODULO_FINANCEIRO_GERENCIAL)
            : false;

        [$inicio, $fim] = $dashboards->resolvePeriod($request);
        $stats = $dashboards->fornecedores($empresaId, $inicio, $fim, $incluirFinanceiro);

        return view('fornecedores.dashboard', compact('stats', 'inicio', 'fim', 'incluirFinanceiro'));
    }

    public function index(Request $request)
    {
        $query = Fornecedor::where('empresa_id', session('empresa_ativa'));

        if ($request->filled('search')) {
            $term = $request->search;
            $termClean = preg_replace('/\D/', '', $term);
            $query->where(function ($q) use ($term, $termClean) {
                $q->where('razao_social', 'like', "%{$term}%")
                    ->orWhere('cnpj', 'like', "%{$termClean}%");
            });
        }

        $fornecedores = $query->latest()->paginate(15);

        return view('fornecedores.index', compact('fornecedores'));
    }

    public function create()
    {
        return view('fornecedores.create');
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $input['cnpj'] = preg_replace('/\D/', '', $input['cnpj'] ?? '');
        $input['cep'] = preg_replace('/\D/', '', $input['cep'] ?? '');
        $request->replace($input);

        $request->validate([
            'razao_social' => 'required|string|max:255',
            'cnpj' => ['required', new CpfCnpj],
            'email' => 'nullable|email',
        ]);

        $existe = Fornecedor::where('empresa_id', session('empresa_ativa'))
            ->where('cnpj', $request->cnpj)
            ->exists();

        if ($existe) {
            return back()->withErrors(['cnpj' => 'Fornecedor já cadastrado nesta empresa.'])->withInput();
        }

        Fornecedor::create([
            'empresa_id' => session('empresa_ativa'),
            ...$request->only([
                'razao_social', 'cnpj', 'inscricao_estadual', 'email', 'telefone',
                'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'uf', 'cidade_codigo',
            ]),
        ]);

        return redirect()->route('fornecedores.index')->with('success', 'Fornecedor cadastrado.');
    }

    public function edit(Fornecedor $fornecedor)
    {
        if ($fornecedor->empresa_id != session('empresa_ativa')) {
            abort(403);
        }

        return view('fornecedores.edit', compact('fornecedor'));
    }

    public function update(Request $request, Fornecedor $fornecedor)
    {
        if ($fornecedor->empresa_id != session('empresa_ativa')) {
            abort(403);
        }

        $input = $request->all();
        $input['cnpj'] = preg_replace('/\D/', '', $input['cnpj'] ?? '');
        $input['cep'] = preg_replace('/\D/', '', $input['cep'] ?? '');
        $request->replace($input);

        $request->validate([
            'razao_social' => 'required|string|max:255',
            'cnpj' => ['required', new CpfCnpj],
        ]);

        $existe = Fornecedor::where('empresa_id', session('empresa_ativa'))
            ->where('cnpj', $request->cnpj)
            ->where('id', '!=', $fornecedor->id)
            ->exists();

        if ($existe) {
            return back()->withErrors(['cnpj' => 'Outro fornecedor já possui este documento.'])->withInput();
        }

        $fornecedor->update($request->only([
            'razao_social', 'cnpj', 'inscricao_estadual', 'email', 'telefone',
            'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'uf', 'cidade_codigo',
        ]));

        return redirect()->route('fornecedores.index')->with('success', 'Fornecedor atualizado.');
    }

    public function destroy(Fornecedor $fornecedor)
    {
        if ($fornecedor->empresa_id != session('empresa_ativa')) {
            abort(403);
        }

        $fornecedor->delete();

        return redirect()->route('fornecedores.index')->with('success', 'Fornecedor excluído.');
    }
}
