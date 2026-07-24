<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'empresa.modulo' => \App\Http\Middleware\EnsureEmpresaModulo::class,
            'empresa.membro' => \App\Http\Middleware\EnsureEmpresaMembership::class,
            'empresa.escrita' => \App\Http\Middleware\EnsureEmpresaCanWrite::class,
            'empresa.perfil' => \App\Http\Middleware\EnsureEmpresaPerfil::class,
            'plataforma.admin' => \App\Http\Middleware\EnsurePlatformAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
