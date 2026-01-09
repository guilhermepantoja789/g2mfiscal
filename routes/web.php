<?php

use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController; // <--- Importante: Usamos este para tudo agora
use App\Http\Controllers\NotaFiscalController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EquipeController;
use Illuminate\Support\Facades\Route;

// 1. Home Page Pública
Route::get('/', function () {
    return view('welcome');
});

// 2. Área Logada (Prefixo /app)
Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {

    // =========================================================================
    // ROTAS DE GESTÃO DE EMPRESA (Unificadas no EmpresaController)
    // =========================================================================

    // 1. Tela de Seleção Inicial (Meus CNPJs)
    Route::get('/selecao', [EmpresaController::class, 'selecao'])
        ->name('empresas.selecao');

    // 2. Entrar no Dashboard da Empresa (Define a sessão)
    Route::get('/entrar/{empresa}', [EmpresaController::class, 'entrar'])
        ->name('empresas.entrar');

    // 3. Tela de Configuração (Dados Fiscais + Certificado)
    Route::get('/empresas/{empresa}/configuracao', [EmpresaController::class, 'configuracao'])
        ->name('empresas.configuracao');

    // 4. CRUD Padrão (Salvar, Atualizar, Excluir)
    // Removemos 'edit' e 'show' pois usamos a rota 'configuracao' acima
    Route::resource('empresas', EmpresaController::class)
        ->except(['show', 'index']);


    // =========================================================================
    // DASHBOARD E OUTROS
    // =========================================================================
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // =========================================================================
    // ROTAS DE NOTAS FISCAIS
    // =========================================================================
    Route::get('/notas', [NotaFiscalController::class, 'index'])->name('notas.index');
    Route::get('/notas/nova', [NotaFiscalController::class, 'create'])->name('notas.create');
    Route::post('/notas/emitir', [NotaFiscalController::class, 'store'])->name('notas.store');
    Route::get('/notas/{id}', [NotaFiscalController::class, 'show'])->name('notas.show');
    Route::get('/notas/{id}/imprimir', [NotaFiscalController::class, 'imprimir'])->name('notas.imprimir');
    Route::get('/notas/{id}/danfse-oficial', [NotaFiscalController::class, 'baixarDanfseOficial'])
        ->name('notas.danfse_oficial');

    // =========================================================================
    // CADASTROS AUXILIARES
    // =========================================================================
    Route::resource('clientes', ClienteController::class);

    // Gestão de Equipe
    Route::post('/equipe/adicionar', [EquipeController::class, 'store'])->name('equipe.store');
    Route::delete('/equipe/{userId}', [EquipeController::class, 'destroy'])->name('equipe.destroy');
    Route::put('/equipe/{userId}/perfil', [EquipeController::class, 'updateRole'])->name('equipe.updateRole');

    Route::resource('servicos', \App\Http\Controllers\ServicoController::class);

    Route::post('/certificados', [CertificadoController::class, 'store'])
        ->name('certificados.store');

    Route::post('/empresas/buscar-im-certificado', [App\Http\Controllers\EmpresaController::class, 'buscarImComCertificado'])
        ->name('empresas.buscar_im_certificado');

    // =========================================================================
    // PERFIL DE USUÁRIO
    // =========================================================================
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rotas de Autenticação do Breeze
require __DIR__.'/auth.php';
