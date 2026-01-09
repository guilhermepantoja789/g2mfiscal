<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Rules\CpfCnpj; // Importe a regra nova
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        // ... (código anterior igual)
        $query = Cliente::where('empresa_id', session('empresa_ativa'));
        if ($request->filled('search')) {
            $term = $request->search;
            // Remove máscara para busca
            $termClean = preg_replace('/\D/', '', $term);

            $query->where(function($q) use ($term, $termClean) {
                $q->where('razao_social', 'like', "%{$term}%")
                    ->orWhere('cnpj', 'like', "%{$termClean}%"); // Busca pelo número limpo
            });
        }
        $clientes = $query->latest()->paginate(10);
        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(Request $request)
    {
        // 1. Limpa máscara antes de validar (para o unique funcionar e a regra CpfCnpj receber limpo se quiser,
        // mas nossa regra CpfCnpj trata string suja, o unique do Laravel precisa de ajuda)
        $input = $request->all();
        $input['cnpj'] = preg_replace('/\D/', '', $input['cnpj']);
        $input['cep'] = preg_replace('/\D/', '', $input['cep']);

        // Substitui o request com dados limpos
        $request->replace($input);

        $request->validate([
            'razao_social' => 'required|string|max:255',
            'cnpj' => ['required', new CpfCnpj], // Validação Customizada
            'email' => 'nullable|email',
        ]);

        // Verifica duplicidade dentro da empresa manualmente para ser mais seguro
        $existe = Cliente::where('empresa_id', session('empresa_ativa'))
            ->where('cnpj', $request->cnpj)->exists();

        if($existe) {
            return back()->withErrors(['cnpj' => 'Este documento já está cadastrado nesta empresa.'])->withInput();
        }

        Cliente::create([
            'empresa_id' => session('empresa_ativa'),
            ...$request->all()
        ]);

        return redirect()->route('clientes.index')->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function edit(Cliente $cliente)
    {
        if ($cliente->empresa_id != session('empresa_ativa')) abort(403);
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        if ($cliente->empresa_id != session('empresa_ativa')) abort(403);

        $input = $request->all();
        $input['cnpj'] = preg_replace('/\D/', '', $input['cnpj']);
        $input['cep'] = preg_replace('/\D/', '', $input['cep']);
        $request->replace($input);

        $request->validate([
            'razao_social' => 'required|string|max:255',
            'cnpj' => ['required', new CpfCnpj],
        ]);

        // Verifica duplicidade (ignorando o próprio ID)
        $existe = Cliente::where('empresa_id', session('empresa_ativa'))
            ->where('cnpj', $request->cnpj)
            ->where('id', '!=', $cliente->id)
            ->exists();

        if($existe) {
            return back()->withErrors(['cnpj' => 'Outro cliente já possui este documento.'])->withInput();
        }

        $cliente->update($request->all());

        return redirect()->route('clientes.index')->with('success', 'Cliente atualizado!');
    }

    public function destroy(Cliente $cliente)
    {
        if ($cliente->empresa_id != session('empresa_ativa')) abort(403);
        $cliente->delete();
        return back()->with('success', 'Cliente removido.');
    }
}
