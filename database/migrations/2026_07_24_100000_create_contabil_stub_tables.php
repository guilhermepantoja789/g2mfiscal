<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stub schema for formal accounting (fase 2B).
 * Tables exist; ContabilPostingService does not post yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos_contas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->string('codigo', 32);
            $table->string('nome');
            $table->string('tipo', 32); // ativo|passivo|receita|despesa|patrimonio
            $table->string('natureza', 1); // D|C
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo']);
        });

        Schema::create('contas_contabeis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('plano_id')->nullable()->constrained('planos_contas')->nullOnDelete();
            $table->string('codigo', 32);
            $table->string('nome');
            $table->string('tipo', 32);
            $table->string('natureza', 1);
            $table->foreignId('conta_pai_id')->nullable()->constrained('contas_contabeis')->nullOnDelete();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo']);
        });

        Schema::create('mapeamentos_contabeis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('origem', 64); // nfse_iss|nfce_venda|nfe_compra|lancamento_pr|estoque_custo
            $table->foreignId('conta_id')->constrained('contas_contabeis')->cascadeOnDelete();
            $table->json('metadados')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'origem', 'conta_id'], 'mapeamentos_contabeis_unique');
        });

        Schema::create('periodos_contabeis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('competencia', 7); // YYYY-MM
            $table->string('status', 16)->default('aberto'); // aberto|fechado
            $table->timestamps();

            $table->unique(['empresa_id', 'competencia']);
        });

        Schema::create('lancamentos_contabeis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('periodo_id')->nullable()->constrained('periodos_contabeis')->nullOnDelete();
            $table->date('data');
            $table->string('historico');
            $table->string('origem_tipo', 64)->nullable();
            $table->unsignedBigInteger('origem_id')->nullable();
            $table->string('status', 16)->default('rascunho'); // rascunho|lancado
            $table->timestamps();

            $table->index(['empresa_id', 'data']);
            $table->index(['origem_tipo', 'origem_id']);
        });

        Schema::create('lancamento_contabil_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lancamento_id')->constrained('lancamentos_contabeis')->cascadeOnDelete();
            $table->foreignId('conta_id')->constrained('contas_contabeis')->restrictOnDelete();
            $table->string('tipo', 1); // D|C
            $table->decimal('valor', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamento_contabil_itens');
        Schema::dropIfExists('lancamentos_contabeis');
        Schema::dropIfExists('periodos_contabeis');
        Schema::dropIfExists('mapeamentos_contabeis');
        Schema::dropIfExists('contas_contabeis');
        Schema::dropIfExists('planos_contas');
    }
};
