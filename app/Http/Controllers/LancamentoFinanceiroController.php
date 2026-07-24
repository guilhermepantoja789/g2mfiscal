<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\FormaPagamento;
use App\Models\Fornecedor;
use App\Models\LancamentoFinanceiro;
use App\Services\Erp\LancamentoFinanceiroService;
use App\Services\Erp\ModuloDashboardService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LancamentoFinanceiroController extends Controller
{
    public function dashboard(Request $request, ModuloDashboardService $dashboards)
    {
        $empresaId = (int) session('empresa_ativa');
        [$inicio, $fim] = $dashboards->resolvePeriod($request);
        $stats = $dashboards->financeiro($empresaId, $inicio, $fim);

        return view('financeiro.dashboard', compact('stats', 'inicio', 'fim'));
    }

    public function index(Request $request)
    {
        $query = LancamentoFinanceiro::query()
            ->where('empresa_id', session('empresa_ativa'))
            ->with(['cliente', 'fornecedor', 'documento', 'cobranca']);

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $lancamentos = $query->latest()->paginate(20);
        $featureAsaas = (bool) config('services.financeiro.enabled');

        return view('financeiro.lancamentos.index', compact('lancamentos', 'featureAsaas'));
    }

    public function create()
    {
        return view('financeiro.lancamentos.form', $this->formData());
    }

    public function store(Request $request, LancamentoFinanceiroService $service)
    {
        $validated = $this->validateLancamento($request);
        $empresa = $this->empresaAtiva();

        $service->criarManual($empresa, [
            ...$validated,
            'status' => $request->boolean('pago_avista')
                ? LancamentoFinanceiro::STATUS_PAGO
                : LancamentoFinanceiro::STATUS_ABERTO,
        ]);

        return redirect()->route('lancamentos.index')
            ->with('success', 'Lançamento criado.');
    }

    public function edit(int $id)
    {
        $lancamento = $this->findLancamento($id);

        if ($lancamento->status !== LancamentoFinanceiro::STATUS_ABERTO) {
            return redirect()->route('lancamentos.index')
                ->with('error', 'Somente lançamentos abertos podem ser editados.');
        }

        return view('financeiro.lancamentos.form', [
            ...$this->formData(),
            'lancamento' => $lancamento,
        ]);
    }

    public function update(Request $request, int $id, LancamentoFinanceiroService $service)
    {
        $lancamento = $this->findLancamento($id);
        $validated = $this->validateLancamento($request);

        $service->atualizar($lancamento, $validated);

        return redirect()->route('lancamentos.index')
            ->with('success', 'Lançamento atualizado.');
    }

    public function baixar(int $id, LancamentoFinanceiroService $service)
    {
        $lancamento = $this->findLancamento($id);
        $service->baixar($lancamento);

        return back()->with('success', 'Lançamento baixado.');
    }

    public function estornar(int $id, LancamentoFinanceiroService $service)
    {
        $lancamento = $this->findLancamento($id);
        $service->estornar($lancamento);

        return back()->with('success', 'Baixa estornada — lançamento reaberto.');
    }

    public function cancelar(int $id, LancamentoFinanceiroService $service)
    {
        $lancamento = $this->findLancamento($id);
        $service->cancelar($lancamento);

        return back()->with('success', 'Lançamento cancelado.');
    }

    public function gerarCobranca(int $id, LancamentoFinanceiroService $service)
    {
        $lancamento = $this->findLancamento($id);

        try {
            $cobranca = $service->gerarCobrancaAsaas($lancamento);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('cobrancas.index')
            ->with('success', 'Cobrança #'.$cobranca->id.' gerada a partir do lançamento.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateLancamento(Request $request): array
    {
        $empresaId = (int) session('empresa_ativa');

        $validated = $request->validate([
            'tipo' => ['required', Rule::in([LancamentoFinanceiro::TIPO_RECEBER, LancamentoFinanceiro::TIPO_PAGAR])],
            'valor' => 'required|numeric|min:0.01',
            'vencimento' => 'required|date',
            'descricao' => 'nullable|string|max:255',
            'cliente_id' => [
                'nullable',
                'integer',
                Rule::exists('clientes', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)),
            ],
            'fornecedor_id' => [
                'nullable',
                'integer',
                Rule::exists('fornecedores', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)),
            ],
            'forma_pagamento_id' => [
                'nullable',
                'integer',
                Rule::exists('formas_pagamento', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)),
            ],
        ]);

        return $validated;
    }

    /**
     * @return array{clientes: \Illuminate\Support\Collection, fornecedores: \Illuminate\Support\Collection, formas: \Illuminate\Support\Collection}
     */
    private function formData(): array
    {
        $empresaId = session('empresa_ativa');

        return [
            'clientes' => Cliente::query()->where('empresa_id', $empresaId)->orderBy('razao_social')->get(),
            'fornecedores' => Fornecedor::query()->where('empresa_id', $empresaId)->orderBy('razao_social')->get(),
            'formas' => FormaPagamento::query()->where('empresa_id', $empresaId)->where('ativo', true)->orderBy('nome')->get(),
        ];
    }

    private function findLancamento(int $id): LancamentoFinanceiro
    {
        return LancamentoFinanceiro::query()
            ->where('empresa_id', session('empresa_ativa'))
            ->findOrFail($id);
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
