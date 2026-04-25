<?php

use App\Http\Controllers\CarteiraController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\CobrancaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DasPagamentoController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\NotaFiscalController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EquipeController;
use App\Http\Controllers\ServicoController;
use App\Http\Controllers\RecorrenciaController;
use Illuminate\Support\Facades\Route;

// 1. Home Page Pública
Route::get('/', function () {
    return view('welcome');
});

// 2. Área Logada (Prefixo /app)
Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {

    // --- GESTÃO DE EMPRESA ---
    Route::get('/selecao', [EmpresaController::class, 'selecao'])->name('empresas.selecao');
    Route::get('/entrar/{empresa}', [EmpresaController::class, 'entrar'])->name('empresas.entrar');
    Route::get('/empresas/{empresa}/configuracao', [EmpresaController::class, 'configuracao'])->name('empresas.configuracao');
    Route::resource('empresas', EmpresaController::class)->except(['show', 'index']);

    // --- DASHBOARD ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/das', [DasPagamentoController::class, 'registrarPagamento'])->name('das.pagar');

    // --- NOTAS FISCAIS ---
    Route::get('/notas', [NotaFiscalController::class, 'index'])->name('notas.index');
    Route::get('/notas/nova', [NotaFiscalController::class, 'create'])->name('notas.create');
    Route::get('/notas/{id}/editar', [NotaFiscalController::class, 'edit'])->name('notas.edit');
    Route::put('/notas/{id}', [NotaFiscalController::class, 'update'])->name('notas.update');
    Route::post('/notas/emitir', [NotaFiscalController::class, 'store'])->name('notas.store');
    Route::get('/notas/{id}', [NotaFiscalController::class, 'show'])->name('notas.show');
    Route::get('/notas/{id}/imprimir', [NotaFiscalController::class, 'imprimir'])->name('notas.imprimir');
    Route::get('/notas/{id}/danfse-oficial', [NotaFiscalController::class, 'baixarDanfseOficial'])->name('notas.danfse_oficial');
    Route::post('/notas/{id}/emitir', [NotaFiscalController::class, 'emitir'])->name('notas.emitir');
    Route::delete('/notas/{id}', [NotaFiscalController::class, 'destroy'])->name('notas.destroy');

    // --- CADASTROS ---
    Route::resource('clientes', ClienteController::class);
    Route::resource('servicos', ServicoController::class);
    Route::resource('recorrencias', RecorrenciaController::class);

    // --- CERTIFICADOS E EQUIPE ---
    Route::post('/equipe/adicionar', [EquipeController::class, 'store'])->name('equipe.store');
    Route::delete('/equipe/{userId}', [EquipeController::class, 'destroy'])->name('equipe.destroy');
    Route::put('/equipe/{userId}/perfil', [EquipeController::class, 'updateRole'])->name('equipe.updateRole');
    Route::post('/certificados', [CertificadoController::class, 'store'])->name('certificados.store');
    Route::post('/empresas/buscar-im-certificado', [EmpresaController::class, 'buscarImComCertificado'])->name('empresas.buscar_im_certificado');

    // =========================================================================
    // FEATURE FLAG: MÓDULO FINANCEIRO
    // Adicione FEATURE_FINANCEIRO=true no seu .env para liberar
    // =========================================================================

    if (env('FEATURE_FINANCEIRO', false)) {

        // --- FUNCIONALIDADES REAIS ---

        // Cobranças
        Route::get('/cobrancas', [CobrancaController::class, 'index'])->name('cobrancas.index');
        Route::post('/cobrancas/{id}/baixar-manual', [CobrancaController::class, 'marcarComoPago'])->name('cobrancas.baixar_manual');

        // Carteira Digital
        Route::prefix('financeiro')->name('carteira.')->group(function() {
            Route::get('/', [CarteiraController::class, 'index'])->name('index');
            Route::post('/ativar', [CarteiraController::class, 'ativarConta'])->name('ativar');
            Route::post('/banco', [CarteiraController::class, 'salvarDadosBancarios'])->name('salvar_banco');
            Route::post('/sacar', [CarteiraController::class, 'solicitarSaque'])->name('sacar');
        });

    } else {

        // --- TELA DE "EM BREVE" ---

        // Redireciona qualquer rota dessas para a view 'em_breve'
        Route::get('/cobrancas', function () { return view('em_breve'); })->name('cobrancas.index');

        // Captura qualquer rota dentro de /financeiro e manda para 'em_breve'
        Route::any('/financeiro/{any?}', function () { return view('em_breve'); })
            ->where('any', '.*')
            ->name('carteira.index');
    }

    // --- PERFIL ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
