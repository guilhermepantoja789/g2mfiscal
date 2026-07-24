<?php

namespace App\Http\Middleware;

use App\Enums\EmpresaPerfil;
use App\Services\Acl\EmpresaAcl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmpresaPerfil
{
    public function __construct(
        private EmpresaAcl $acl,
    ) {}

    /**
     * @param  string  ...$perfis  Valores de EmpresaPerfil (admin, operador, contador)
     */
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        if ($perfis === []) {
            abort(500, 'Middleware empresa.perfil requer ao menos um perfil.');
        }

        if ($this->acl->isPlatformAdmin() && in_array('admin', $perfis, true)) {
            return $next($request);
        }

        $atual = $this->acl->perfilAtivo();
        if (! $atual) {
            abort(403, 'Sem perfil na empresa ativa.');
        }

        $permitidos = [];
        foreach ($perfis as $perfil) {
            $enum = EmpresaPerfil::tryFrom($perfil);
            if ($enum) {
                $permitidos[] = $enum;
            }
        }

        if (! in_array($atual, $permitidos, true)) {
            abort(403, 'Perfil sem permissão para esta área.');
        }

        return $next($request);
    }
}
