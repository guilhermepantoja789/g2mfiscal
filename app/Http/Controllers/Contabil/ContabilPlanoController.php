<?php

namespace App\Http\Controllers\Contabil;

use App\Http\Controllers\Controller;
use App\Models\ContaContabil;
use App\Models\Empresa;
use App\Models\MapeamentoContabil;
use App\Services\Acl\EmpresaAcl;
use App\Services\Contabil\ContabilPlanoService;
use App\Services\Contabil\ContabilPostingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContabilPlanoController extends Controller
{
    public function __construct(
        private ContabilPlanoService $plano,
        private EmpresaAcl $acl,
    ) {}

    public function index()
    {
        $empresa = $this->empresaAtiva();
        $this->plano->garantirPadrao($empresa);

        $contas = $this->plano->listarContas($empresa);
        $mapeamentos = $this->plano->listarMapeamentos($empresa);
        $origens = [
            ContabilPostingService::MAP_NFCE_VENDA => 'NFC-e venda',
            ContabilPostingService::MAP_NFSE => 'NFS-e / ISS',
            ContabilPostingService::MAP_NFE_COMPRA => 'NF-e compra',
        ];

        return view('contabil.plano-contas', compact('empresa', 'contas', 'mapeamentos', 'origens'));
    }

    public function storeConta(Request $request)
    {
        $empresa = $this->empresaAtiva();
        $validated = $request->validate([
            'codigo' => 'required|string|max:32',
            'nome' => 'required|string|max:255',
            'tipo' => ['required', Rule::in(['ativo', 'passivo', 'receita', 'despesa', 'patrimonio'])],
            'natureza' => ['required', Rule::in(['D', 'C', 'd', 'c'])],
            'ativo' => 'nullable|boolean',
        ]);

        try {
            $this->plano->criarConta($empresa, [
                ...$validated,
                'ativo' => $request->boolean('ativo', true),
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Conta criada.');
    }

    public function updateConta(Request $request, ContaContabil $conta)
    {
        $this->authorizeEmpresa($conta->empresa_id);
        $validated = $request->validate([
            'codigo' => 'required|string|max:32',
            'nome' => 'required|string|max:255',
            'tipo' => ['required', Rule::in(['ativo', 'passivo', 'receita', 'despesa', 'patrimonio'])],
            'natureza' => ['required', Rule::in(['D', 'C', 'd', 'c'])],
            'ativo' => 'nullable|boolean',
        ]);

        try {
            $this->plano->atualizarConta($conta, [
                ...$validated,
                'ativo' => $request->boolean('ativo', true),
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Conta atualizada.');
    }

    public function storeMapeamento(Request $request)
    {
        $empresa = $this->empresaAtiva();
        $validated = $request->validate([
            'origem' => ['required', Rule::in([
                ContabilPostingService::MAP_NFCE_VENDA,
                ContabilPostingService::MAP_NFSE,
                ContabilPostingService::MAP_NFE_COMPRA,
            ])],
            'conta_id' => [
                'required',
                'integer',
                Rule::exists('contas_contabeis', 'id')->where(fn ($q) => $q->where('empresa_id', $empresa->id)),
            ],
            'papel' => ['required', Rule::in(['D', 'C'])],
        ]);

        try {
            $this->plano->salvarMapeamento($empresa, $validated);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Mapeamento salvo.');
    }

    public function destroyMapeamento(MapeamentoContabil $mapeamento)
    {
        $this->authorizeEmpresa($mapeamento->empresa_id);
        $this->plano->removerMapeamento($mapeamento);

        return back()->with('success', 'Mapeamento removido.');
    }

    private function authorizeEmpresa(int $empresaId): void
    {
        if ((int) $this->acl->empresaIdAtiva() !== $empresaId) {
            abort(403);
        }
    }

    private function empresaAtiva(): Empresa
    {
        $id = $this->acl->empresaIdAtiva();
        abort_unless($id, 403, 'Nenhuma empresa ativa.');

        return Empresa::findOrFail($id);
    }
}
