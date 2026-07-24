<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmpresaModulo
{
    public function handle(Request $request, Closure $next, string $modulo = 'erp'): Response
    {
        $empresaId = session('empresa_ativa');
        if (! $empresaId) {
            return redirect()->route('empresas.selecao');
        }

        $empresa = Empresa::find($empresaId);
        if (! $empresa) {
            return redirect()->route('empresas.selecao');
        }

        if (! $empresa->temModulo($modulo)) {
            abort(403, 'Módulo '.$modulo.' não habilitado para esta empresa.');
        }

        return $next($request);
    }
}
