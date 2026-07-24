<?php

use App\Http\Controllers\CarteiraController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\CobrancaController;
use App\Http\Controllers\Contabil\ContabilHubController;
use App\Http\Controllers\Contabil\ContabilPlanoController;
use App\Http\Controllers\Contabil\ContabilRelatorioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DasPagamentoController;
use App\Http\Controllers\DocumentoComercialController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\FormaPagamentoController;
use App\Http\Controllers\LancamentoFinanceiroController;
use App\Http\Controllers\NotaFiscalController;
use App\Http\Controllers\NfceController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EquipeController;
use App\Http\Controllers\PdvController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\ServicoController;
use App\Http\Controllers\RecorrenciaController;
use Illuminate\Support\Facades\Route;

// 1. Home Page Pública
Route::get('/', function () {
    return view('welcome');
});

// 2. Área Logada (Prefixo /app)
Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {

    // --- SEM empresa_ativa / membership ---
    Route::get('/selecao', [EmpresaController::class, 'selecao'])->name('empresas.selecao');
    Route::get('/entrar/{empresa}', [EmpresaController::class, 'entrar'])->name('empresas.entrar');
    Route::get('/empresas/create', [EmpresaController::class, 'create'])->name('empresas.create');
    Route::post('/empresas', [EmpresaController::class, 'store'])->name('empresas.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('plataforma.admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/vinculos', [\App\Http\Controllers\Admin\VinculosController::class, 'index'])->name('vinculos.index');
        Route::post('/vinculos', [\App\Http\Controllers\Admin\VinculosController::class, 'store'])->name('vinculos.store');
        Route::put('/vinculos/{empresa}/{user}', [\App\Http\Controllers\Admin\VinculosController::class, 'update'])->name('vinculos.update');
        Route::delete('/vinculos/{empresa}/{user}', [\App\Http\Controllers\Admin\VinculosController::class, 'destroy'])->name('vinculos.destroy');
    });

    // --- COM membership na empresa ativa ---
    Route::middleware('empresa.membro')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/dashboard/das', [DasPagamentoController::class, 'registrarPagamento'])
            ->middleware('empresa.escrita')
            ->name('das.pagar');

        // --- NOTAS FISCAIS (leitura liberada; escrita bloqueada para contador) ---
        Route::middleware('empresa.escrita')->group(function () {
            Route::get('/notas/nova', [NotaFiscalController::class, 'create'])->name('notas.create');
            Route::post('/notas/emitir', [NotaFiscalController::class, 'store'])->name('notas.store');
        });
        Route::get('/notas', [NotaFiscalController::class, 'index'])->name('notas.index');
        Route::get('/notas/{id}/imprimir', [NotaFiscalController::class, 'imprimir'])->name('notas.imprimir');
        Route::get('/notas/{id}/danfse-oficial', [NotaFiscalController::class, 'baixarDanfseOficial'])->name('notas.danfse_oficial');
        Route::middleware('empresa.escrita')->group(function () {
            Route::get('/notas/{id}/editar', [NotaFiscalController::class, 'edit'])->name('notas.edit');
            Route::put('/notas/{id}', [NotaFiscalController::class, 'update'])->name('notas.update');
            Route::post('/notas/{id}/emitir', [NotaFiscalController::class, 'emitir'])->name('notas.emitir');
            Route::delete('/notas/{id}', [NotaFiscalController::class, 'destroy'])->name('notas.destroy');
        });
        Route::get('/notas/{id}', [NotaFiscalController::class, 'show'])->name('notas.show');

        // --- NFC-e ---
        Route::middleware('empresa.escrita')->group(function () {
            Route::get('/nfces/nova', [NfceController::class, 'create'])->name('nfces.create');
            Route::get('/nfces/inutilizar', [NfceController::class, 'inutilizarForm'])->name('nfces.inutilizar.form');
            Route::post('/nfces/inutilizar', [NfceController::class, 'inutilizar'])->name('nfces.inutilizar');
            Route::get('/nfces/laboratorio', [NfceController::class, 'laboratorio'])->name('nfces.laboratorio');
            Route::post('/nfces/laboratorio/status', [NfceController::class, 'statusServico'])->name('nfces.laboratorio.status');
            Route::post('/nfces/laboratorio/emitir', [NfceController::class, 'emitirTeste'])->name('nfces.laboratorio.emitir');
            Route::post('/nfces', [NfceController::class, 'store'])->name('nfces.store');
        });
        Route::get('/nfces', [NfceController::class, 'dashboard'])->name('nfces.dashboard');
        Route::get('/nfces/lista', [NfceController::class, 'index'])->name('nfces.index');
        Route::get('/nfces/{id}/imprimir', [NfceController::class, 'imprimir'])->name('nfces.imprimir');
        Route::middleware('empresa.escrita')->group(function () {
            Route::post('/nfces/{id}/cancelar', [NfceController::class, 'cancelar'])->name('nfces.cancelar');
            Route::post('/nfces/{id}/transmitir', [NfceController::class, 'transmitir'])->name('nfces.transmitir');
            Route::post('/nfces/{id}/recuperar-duplicidade', [NfceController::class, 'recuperarDuplicidade'])->name('nfces.recuperar-duplicidade');
        });
        Route::get('/nfces/{id}', [NfceController::class, 'show'])->name('nfces.show');

        Route::post('/notificacoes/ler-todas', function () {
            auth()->user()->unreadNotifications->markAsRead();

            return back();
        })->name('notificacoes.ler-todas');

        // --- CADASTROS (leitura + escrita) ---
        Route::get('/clientes/buscar', [ClienteController::class, 'buscar'])->name('clientes.buscar');
        Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
        Route::get('/servicos', [ServicoController::class, 'index'])->name('servicos.index');
        Route::get('/recorrencias', [RecorrenciaController::class, 'index'])->name('recorrencias.index');
        Route::middleware('empresa.escrita')->group(function () {
            Route::resource('clientes', ClienteController::class)->except(['index', 'show']);
            Route::resource('servicos', ServicoController::class)->except(['index', 'show']);
            Route::resource('recorrencias', RecorrenciaController::class)->except(['index', 'show']);
        });

        // --- ERP ---
        Route::middleware('empresa.modulo:erp')->group(function () {
            Route::get('/produtos', [ProdutoController::class, 'dashboard'])->name('produtos.dashboard');
            Route::get('/produtos/lista', [ProdutoController::class, 'index'])->name('produtos.index');
            Route::get('/fornecedores', [FornecedorController::class, 'dashboard'])->name('fornecedores.dashboard');
            Route::get('/fornecedores/lista', [FornecedorController::class, 'index'])->name('fornecedores.index');
            Route::get('/documentos', [DocumentoComercialController::class, 'dashboard'])->name('documentos.dashboard');
            Route::get('/documentos/lista', [DocumentoComercialController::class, 'index'])->name('documentos.index');
            Route::middleware('empresa.escrita')->group(function () {
                Route::resource('produtos', ProdutoController::class)->except(['show', 'index']);
                Route::resource('fornecedores', FornecedorController::class)
                    ->parameters(['fornecedores' => 'fornecedor'])
                    ->except(['show', 'index']);
                Route::get('/documentos/novo', [DocumentoComercialController::class, 'create'])->name('documentos.create');
                Route::post('/documentos', [DocumentoComercialController::class, 'store'])->name('documentos.store');
                Route::get('/documentos/importar-xml', [DocumentoComercialController::class, 'importarXmlForm'])->name('documentos.importar_xml');
                Route::post('/documentos/importar-xml/preview', [DocumentoComercialController::class, 'importarXmlPreview'])->name('documentos.importar_xml.preview');
                Route::post('/documentos/importar-xml/confirmar', [DocumentoComercialController::class, 'importarXmlConfirmar'])->name('documentos.importar_xml.confirmar');
                Route::post('/documentos/{id}/confirmar', [DocumentoComercialController::class, 'confirmar'])->name('documentos.confirmar');
            });
            Route::get('/documentos/{id}', [DocumentoComercialController::class, 'show'])->name('documentos.show');
            Route::get('/estoque', [EstoqueController::class, 'dashboard'])->name('estoque.dashboard');
            Route::get('/estoque/saldos', [EstoqueController::class, 'index'])->name('estoque.saldos');
            Route::get('/estoque/{produtoId}', [EstoqueController::class, 'show'])->name('estoque.show');
        });

        Route::middleware(['empresa.modulo:erp', 'empresa.modulo:pdv', 'empresa.escrita'])->group(function () {
            Route::get('/pdv', [PdvController::class, 'index'])->name('pdv.index');
            Route::get('/pdv/produtos', [PdvController::class, 'buscarProdutos'])->name('pdv.produtos');
            Route::get('/pdv/vendas/{documento}', [PdvController::class, 'statusVenda'])->name('pdv.vendas.status');
            Route::post('/pdv/finalizar', [PdvController::class, 'finalizar'])->name('pdv.finalizar');
        });

        Route::middleware(['empresa.modulo:erp', 'empresa.modulo:financeiro_gerencial'])->group(function () {
            Route::get('/financeiro', [LancamentoFinanceiroController::class, 'dashboard'])->name('financeiro.dashboard');
            Route::get('/financeiro/lancamentos', [LancamentoFinanceiroController::class, 'index'])->name('lancamentos.index');

            Route::middleware('empresa.escrita')->group(function () {
                Route::get('/financeiro/lancamentos/criar', [LancamentoFinanceiroController::class, 'create'])->name('lancamentos.create');
                Route::post('/financeiro/lancamentos', [LancamentoFinanceiroController::class, 'store'])->name('lancamentos.store');
                Route::get('/financeiro/lancamentos/{id}/editar', [LancamentoFinanceiroController::class, 'edit'])->name('lancamentos.edit');
                Route::put('/financeiro/lancamentos/{id}', [LancamentoFinanceiroController::class, 'update'])->name('lancamentos.update');
                Route::post('/financeiro/lancamentos/{id}/baixar', [LancamentoFinanceiroController::class, 'baixar'])->name('lancamentos.baixar');
                Route::post('/financeiro/lancamentos/{id}/estornar', [LancamentoFinanceiroController::class, 'estornar'])->name('lancamentos.estornar');
                Route::post('/financeiro/lancamentos/{id}/cancelar', [LancamentoFinanceiroController::class, 'cancelar'])->name('lancamentos.cancelar');
                Route::post('/financeiro/lancamentos/{id}/gerar-cobranca', [LancamentoFinanceiroController::class, 'gerarCobranca'])->name('lancamentos.gerar_cobranca');
                Route::resource('formas-pagamento', FormaPagamentoController::class)
                    ->parameters(['formas-pagamento' => 'formas_pagamento'])
                    ->except(['show']);
            });
        });

        // --- ÁREA CONTÁBIL (admin + contador; módulo opt-in) ---
        Route::middleware(['empresa.modulo:contabil', 'empresa.perfil:admin,contador'])
            ->prefix('contabil')
            ->name('contabil.')
            ->group(function () {
                Route::get('/', [ContabilHubController::class, 'dashboard'])->name('dashboard');
                Route::get('/livro-servicos', [ContabilHubController::class, 'livroServicos'])->name('livro_servicos');
                Route::get('/livro-cupons', [ContabilHubController::class, 'livroCupons'])->name('livro_cupons');
                Route::get('/livro-entradas', [ContabilHubController::class, 'livroEntradas'])->name('livro_entradas');
                Route::get('/exportacoes', [ContabilHubController::class, 'exportacoes'])->name('exportacoes');
                Route::get('/exportacoes/csv', [ContabilHubController::class, 'exportCsv'])->name('export.csv');
                Route::get('/exportacoes/zip', [ContabilHubController::class, 'exportZip'])->name('export.zip');
                Route::get('/cadastro-fiscal', [ContabilHubController::class, 'cadastroFiscal'])->name('cadastro_fiscal');
                Route::get('/plano-contas', [ContabilPlanoController::class, 'index'])->name('plano.index');
                Route::get('/dre', [ContabilRelatorioController::class, 'dre'])->name('dre');
                Route::get('/balanco', [ContabilRelatorioController::class, 'balanco'])->name('balanco');

                Route::middleware('empresa.escrita')->group(function () {
                    Route::post('/plano-contas/contas', [ContabilPlanoController::class, 'storeConta'])->name('plano.contas.store');
                    Route::put('/plano-contas/contas/{conta}', [ContabilPlanoController::class, 'updateConta'])->name('plano.contas.update');
                    Route::post('/plano-contas/mapeamentos', [ContabilPlanoController::class, 'storeMapeamento'])->name('plano.mapeamentos.store');
                    Route::delete('/plano-contas/mapeamentos/{mapeamento}', [ContabilPlanoController::class, 'destroyMapeamento'])->name('plano.mapeamentos.destroy');
                });
            });

        // --- ADMIN: config, equipe, certificado, CRUD empresa ---
        Route::middleware('empresa.perfil:admin')->group(function () {
            Route::get('/empresas/{empresa}/configuracao', [EmpresaController::class, 'configuracao'])->name('empresas.configuracao');
            Route::get('/empresas/{empresa}/edit', [EmpresaController::class, 'edit'])->name('empresas.edit');
            Route::put('/empresas/{empresa}', [EmpresaController::class, 'update'])->name('empresas.update');
            Route::patch('/empresas/{empresa}', [EmpresaController::class, 'update']);
            Route::delete('/empresas/{empresa}', [EmpresaController::class, 'destroy'])->name('empresas.destroy');

            Route::post('/equipe/adicionar', [EquipeController::class, 'store'])->name('equipe.store');
            Route::delete('/equipe/{userId}', [EquipeController::class, 'destroy'])->name('equipe.destroy');
            Route::put('/equipe/{userId}/perfil', [EquipeController::class, 'updateRole'])->name('equipe.updateRole');
            Route::post('/certificados', [CertificadoController::class, 'store'])->name('certificados.store');
            Route::post('/empresas/buscar-im-certificado', [EmpresaController::class, 'buscarImComCertificado'])->name('empresas.buscar_im_certificado');
        });

        if (env('FEATURE_FINANCEIRO', false)) {
            Route::get('/cobrancas', [CobrancaController::class, 'index'])->name('cobrancas.index');
            Route::post('/cobrancas/{id}/baixar-manual', [CobrancaController::class, 'marcarComoPago'])
                ->middleware('empresa.escrita')
                ->name('cobrancas.baixar_manual');

            Route::prefix('carteira')->name('carteira.')->group(function () {
                Route::get('/', [CarteiraController::class, 'index'])->name('index');
                Route::middleware('empresa.escrita')->group(function () {
                    Route::post('/ativar', [CarteiraController::class, 'ativarConta'])->name('ativar');
                    Route::post('/banco', [CarteiraController::class, 'salvarDadosBancarios'])->name('salvar_banco');
                    Route::post('/sacar', [CarteiraController::class, 'solicitarSaque'])->name('sacar');
                });
            });
        } else {
            Route::get('/cobrancas', function () {
                return view('em_breve');
            })->name('cobrancas.index');

            Route::get('/carteira', function () {
                return view('em_breve');
            })->name('carteira.index');
            Route::any('/carteira/ativar', function () {
                return view('em_breve');
            });
            Route::any('/carteira/banco', function () {
                return view('em_breve');
            });
            Route::any('/carteira/sacar', function () {
                return view('em_breve');
            });
        }
    });
});

require __DIR__.'/auth.php';
