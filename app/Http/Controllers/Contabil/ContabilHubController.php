<?php

namespace App\Http\Controllers\Contabil;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Services\Acl\EmpresaAcl;
use App\Services\Contabil\ContabilHubService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContabilHubController extends Controller
{
    public function __construct(
        private ContabilHubService $hub,
        private EmpresaAcl $acl,
    ) {}

    public function dashboard(Request $request)
    {
        $empresa = $this->empresaAtiva();
        [$inicio, $fim] = $this->hub->resolvePeriod($request);
        $stats = $this->hub->painel($empresa->id, $inicio, $fim);

        return view('contabil.dashboard', compact('empresa', 'stats', 'inicio', 'fim'));
    }

    public function livroServicos(Request $request)
    {
        $empresa = $this->empresaAtiva();
        [$inicio, $fim] = $this->hub->resolvePeriod($request);
        $notas = $this->hub->livroServicos($empresa->id, $inicio, $fim, $request->input('status'));

        return view('contabil.livro-servicos', compact('empresa', 'notas', 'inicio', 'fim'));
    }

    public function livroCupons(Request $request)
    {
        $empresa = $this->empresaAtiva();
        [$inicio, $fim] = $this->hub->resolvePeriod($request);
        $cupons = $this->hub->livroCupons($empresa->id, $inicio, $fim, $request->input('status'));

        return view('contabil.livro-cupons', compact('empresa', 'cupons', 'inicio', 'fim'));
    }

    public function livroEntradas(Request $request)
    {
        $empresa = $this->empresaAtiva();
        [$inicio, $fim] = $this->hub->resolvePeriod($request);
        $documentos = $this->hub->livroEntradas($empresa->id, $inicio, $fim);

        return view('contabil.livro-entradas', compact('empresa', 'documentos', 'inicio', 'fim'));
    }

    public function exportacoes(Request $request)
    {
        $empresa = $this->empresaAtiva();
        [$inicio, $fim] = $this->hub->resolvePeriod($request);

        return view('contabil.exportacoes', compact('empresa', 'inicio', 'fim'));
    }

    public function exportCsv(Request $request)
    {
        $empresa = $this->empresaAtiva();
        $request->validate([
            'tipo' => ['required', Rule::in(['nfse', 'nfce', 'compras'])],
        ]);
        [$inicio, $fim] = $this->hub->resolvePeriod($request);

        return $this->hub->exportCsv($empresa->id, $inicio, $fim, $request->tipo);
    }

    public function exportZip(Request $request)
    {
        $empresa = $this->empresaAtiva();
        $request->validate([
            'tipo' => ['required', Rule::in(['nfse', 'nfce', 'compras'])],
        ]);
        [$inicio, $fim] = $this->hub->resolvePeriod($request);

        return $this->hub->exportZipXml($empresa->id, $inicio, $fim, $request->tipo);
    }

    public function cadastroFiscal()
    {
        $empresa = $this->empresaAtiva();

        return view('contabil.cadastro-fiscal', compact('empresa'));
    }

    private function empresaAtiva(): Empresa
    {
        $id = $this->acl->empresaIdAtiva();
        abort_unless($id, 403, 'Nenhuma empresa ativa.');

        return Empresa::findOrFail($id);
    }
}
