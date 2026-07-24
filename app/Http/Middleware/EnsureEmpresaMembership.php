<?php

namespace App\Http\Middleware;

use App\Services\Acl\EmpresaAcl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmpresaMembership
{
    public function __construct(
        private EmpresaAcl $acl,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $empresaId = $this->acl->empresaIdAtiva();
        if (! $empresaId) {
            return redirect()->route('empresas.selecao');
        }

        if ($this->acl->isPlatformAdmin(Auth::user())) {
            if (! \App\Models\Empresa::query()->whereKey($empresaId)->exists()) {
                session()->forget('empresa_ativa');

                return redirect()->route('empresas.selecao')
                    ->withErrors(['erro' => 'Empresa ativa inválida.']);
            }

            return $next($request);
        }

        if (! $this->acl->pertenceAEmpresa(Auth::user(), $empresaId)) {
            session()->forget('empresa_ativa');

            return redirect()->route('empresas.selecao')
                ->withErrors(['erro' => 'Você não tem mais acesso a esta empresa.']);
        }

        return $next($request);
    }
}
