<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('razao_social');
            $table->string('cnpj', 14);
            $table->string('inscricao_estadual')->nullable();
            $table->string('email')->nullable();
            $table->string('telefone')->nullable();
            $table->string('cep', 8)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero')->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('cidade_codigo', 7)->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'cnpj']);
        });

        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('ean', 14)->nullable();
            $table->string('descricao');
            $table->string('ncm', 8)->nullable();
            $table->string('cfop', 4)->default('5102');
            $table->string('csosn', 3)->default('102');
            $table->string('unidade', 6)->default('UN');
            $table->decimal('preco_venda', 12, 2)->default(0);
            $table->decimal('custo_medio', 12, 4)->default(0);
            $table->decimal('estoque_atual', 14, 4)->default(0);
            $table->boolean('controla_estoque')->default(true);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'sku']);
            $table->index(['empresa_id', 'ean']);
        });

        Schema::create('empresa_modulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('modulo', 40);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'modulo']);
        });

        Schema::create('documentos_comerciais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('tipo', 20); // venda|compra
            $table->string('canal_fiscal', 20); // nfse|nfce|nfe_entrada
            $table->string('status', 30)->default('rascunho');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores')->nullOnDelete();
            $table->decimal('valor_total', 12, 2)->default(0);
            $table->string('forma_pagamento', 10)->nullable(); // 01|03|04|17|prazo
            $table->date('vencimento')->nullable();
            $table->boolean('pago_avista')->default(false);
            $table->string('chave_nfe', 44)->nullable();
            $table->longText('xml_nfe')->nullable();
            $table->string('numero_nfe')->nullable();
            $table->string('serie_nfe')->nullable();
            $table->text('observacoes')->nullable();
            $table->string('mensagem_erro')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'tipo', 'status']);
            $table->unique(['empresa_id', 'chave_nfe']);
        });

        Schema::create('documento_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_comercial_id')->constrained('documentos_comerciais')->cascadeOnDelete();
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $table->string('descricao');
            $table->string('ncm', 8)->nullable();
            $table->string('cfop', 4)->nullable();
            $table->string('csosn', 3)->nullable();
            $table->string('unidade', 6)->nullable();
            $table->string('codigo_fornecedor')->nullable();
            $table->string('ean', 14)->nullable();
            $table->decimal('quantidade', 14, 4)->default(1);
            $table->decimal('valor_unitario', 12, 4)->default(0);
            $table->decimal('valor_total', 12, 2)->default(0);
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();
        });

        Schema::create('estoque_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->foreignId('documento_comercial_id')->nullable()->constrained('documentos_comerciais')->nullOnDelete();
            $table->string('tipo', 20); // entrada|saida|ajuste
            $table->decimal('quantidade', 14, 4);
            $table->decimal('custo_unitario', 12, 4)->default(0);
            $table->decimal('saldo_apos', 14, 4);
            $table->string('origem', 40)->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'produto_id', 'created_at']);
        });

        Schema::create('lancamentos_financeiros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('documento_comercial_id')->nullable()->constrained('documentos_comerciais')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores')->nullOnDelete();
            $table->foreignId('cobranca_id')->nullable()->constrained('cobrancas')->nullOnDelete();
            $table->string('tipo', 20); // receber|pagar
            $table->string('status', 20)->default('aberto'); // aberto|pago|cancelado
            $table->decimal('valor', 12, 2);
            $table->date('vencimento')->nullable();
            $table->timestamp('pago_em')->nullable();
            $table->string('descricao')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'tipo', 'status']);
        });

        Schema::table('nota_fiscais', function (Blueprint $table) {
            $table->foreignId('documento_comercial_id')
                ->nullable()
                ->after('servico_id')
                ->constrained('documentos_comerciais')
                ->nullOnDelete();
        });

        Schema::table('nfces', function (Blueprint $table) {
            $table->foreignId('documento_comercial_id')
                ->nullable()
                ->after('empresa_id')
                ->constrained('documentos_comerciais')
                ->nullOnDelete();
        });

        Schema::table('cobrancas', function (Blueprint $table) {
            $table->foreignId('lancamento_financeiro_id')
                ->nullable()
                ->after('nota_fiscal_id')
                ->constrained('lancamentos_financeiros')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cobrancas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lancamento_financeiro_id');
        });

        Schema::table('nfces', function (Blueprint $table) {
            $table->dropConstrainedForeignId('documento_comercial_id');
        });

        Schema::table('nota_fiscais', function (Blueprint $table) {
            $table->dropConstrainedForeignId('documento_comercial_id');
        });

        Schema::dropIfExists('lancamentos_financeiros');
        Schema::dropIfExists('estoque_movimentacoes');
        Schema::dropIfExists('documento_itens');
        Schema::dropIfExists('documentos_comerciais');
        Schema::dropIfExists('empresa_modulos');
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('fornecedores');
    }
};
