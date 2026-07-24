<?php

namespace App\Http\Middleware;

use App\Services\Acl\EmpresaAcl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia escrita operacional (emissão, PDV, cadastros mutáveis) para perfil contador.
 */
class EnsureEmpresaCanWrite
{
    public function __construct(
        private EmpresaAcl $acl,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->acl->podeEscreverOperacional()) {
            abort(403, 'Perfil contador não pode executar esta ação operacional.');
        }

        return $next($request);
    }
}
