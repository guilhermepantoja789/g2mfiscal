<?php

namespace App\Http\Controllers\Contabil;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Services\Acl\EmpresaAcl;
use App\Services\Contabil\ContabilHubService;
use App\Services\Contabil\ContabilRelatorioService;
use Illuminate\Http\Request;

class ContabilRelatorioController extends Controller
{
    public function __construct(
        private ContabilHubService $hub,
        private ContabilRelatorioService $relatorios,
        private EmpresaAcl $acl,
    ) {}

    public function dre(Request $request)
    {
        $empresa = $this->empresaAtiva();
        [$inicio, $fim] = $this->hub->resolvePeriod($request);
        $dre = $this->relatorios->dre($empresa->id, $inicio, $fim);

        return view('contabil.dre', compact('empresa', 'dre', 'inicio', 'fim'));
    }

    public function balanco(Request $request)
    {
        $empresa = $this->empresaAtiva();
        $ate = $request->filled('data_fim')
            ? \Carbon\Carbon::parse($request->data_fim)->endOfDay()
            : now()->endOfDay();

        $balanco = $this->relatorios->balanco($empresa->id, $ate);

        return view('contabil.balanco', compact('empresa', 'balanco', 'ate'));
    }

    private function empresaAtiva(): Empresa
    {
        $id = $this->acl->empresaIdAtiva();
        abort_unless($id, 403, 'Nenhuma empresa ativa.');

        return Empresa::findOrFail($id);
    }
}
