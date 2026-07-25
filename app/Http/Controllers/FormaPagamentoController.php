<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\FormaPagamento;
use App\Services\Erp\FormaPagamentoService;
use Illuminate\Http\Request;

class FormaPagamentoController extends Controller
{
    public function index(FormaPagamentoService $service)
    {
        $empresa = $this->empresaAtiva();
        $service->garantirDefaults($empresa);

        $formas = FormaPagamento::query()
            ->where('empresa_id', $empresa->id)
            ->with('contaContabil')
            ->orderBy('nome')
            ->get();

        return view('formas-pagamento.index', compact('formas'));
    }

    public function create(FormaPagamentoService $service)
    {
        $empresa = $this->empresaAtiva();
        $contas = $service->contasParaSelect($empresa);

        return view('formas-pagamento.create', compact('contas'));
    }

    public function store(Request $request, FormaPagamentoService $service)
    {
        $empresa = $this->empresaAtiva();
        $validated = $this->validateForma($request, $empresa);

        FormaPagamento::create([
            'empresa_id' => $empresa->id,
            ...$validated,
            'ativo' => $request->boolean('ativo', true),
            'gera_lancamento' => $request->boolean('gera_lancamento', true),
        ]);

        return redirect()->route('formas-pagamento.index')
            ->with('success', 'Forma de pagamento cadastrada.');
    }

    public function edit(FormaPagamento $formas_pagamento, FormaPagamentoService $service)
    {
        $this->authorizeEmpresa($formas_pagamento->empresa_id);
        $contas = $service->contasParaSelect($this->empresaAtiva());

        return view('formas-pagamento.edit', [
            'forma' => $formas_pagamento,
            'contas' => $contas,
        ]);
    }

    public function update(Request $request, FormaPagamento $formas_pagamento)
    {
        $this->authorizeEmpresa($formas_pagamento->empresa_id);
        $validated = $this->validateForma($request, $this->empresaAtiva());

        $formas_pagamento->update([
            ...$validated,
            'ativo' => $request->boolean('ativo', true),
            'gera_lancamento' => $request->boolean('gera_lancamento', true),
        ]);

        return redirect()->route('formas-pagamento.index')
            ->with('success', 'Forma de pagamento atualizada.');
    }

    public function destroy(FormaPagamento $formas_pagamento)
    {
        $this->authorizeEmpresa($formas_pagamento->empresa_id);
        $formas_pagamento->delete();

        return redirect()->route('formas-pagamento.index')
            ->with('success', 'Forma de pagamento removida.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateForma(Request $request, Empresa $empresa): array
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:10',
            'nome' => 'required|string|max:120',
            'tipo_liquidacao' => 'required|in:avista,prazo',
            'dias_recebimento' => 'nullable|integer|min:0|max:3650',
            'parcelas' => 'nullable|integer|min:1|max:48',
            'juros_percentual' => 'nullable|numeric|min:0|max:100',
            'conta_contabil_id' => 'nullable|integer|exists:contas_contabeis,id',
            'ativo' => 'nullable|boolean',
            'gera_lancamento' => 'nullable|boolean',
        ]);

        if (! empty($validated['conta_contabil_id'])) {
            $ok = \App\Models\ContaContabil::query()
                ->where('empresa_id', $empresa->id)
                ->whereKey($validated['conta_contabil_id'])
                ->exists();
            if (! $ok) {
                abort(422, 'Conta contábil inválida para a empresa.');
            }
        } else {
            $validated['conta_contabil_id'] = null;
        }

        $validated['dias_recebimento'] = (int) ($validated['dias_recebimento'] ?? 0);
        $validated['parcelas'] = max(1, (int) ($validated['parcelas'] ?? 1));
        $validated['juros_percentual'] = (float) ($validated['juros_percentual'] ?? 0);

        return $validated;
    }

    private function authorizeEmpresa(int $empresaId): void
    {
        if ((int) session('empresa_ativa') !== $empresaId) {
            abort(403);
        }
    }

    private function empresaAtiva(): Empresa
    {
        $empresa = Empresa::find(session('empresa_ativa'));
        if (! $empresa) {
            abort(403, 'Selecione uma empresa.');
        }

        return $empresa;
    }
}
