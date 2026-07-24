<?php

namespace App\Http\Middleware;

use App\Services\Acl\EmpresaAcl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    public function __construct(
        private EmpresaAcl $acl,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->acl->isPlatformAdmin()) {
            abort(403, 'Apenas administradores de plataforma.');
        }

        return $next($request);
    }
}
