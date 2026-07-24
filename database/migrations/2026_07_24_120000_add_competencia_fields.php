<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datas de competência para livros fiscais / postagem contábil (fase 2B).
 * Fallback nos hubs: COALESCE(campo, created_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_comerciais', function (Blueprint $table) {
            $table->date('data_competencia')->nullable()->after('valor_total');
            $table->index(['empresa_id', 'data_competencia']);
        });

        Schema::table('nfces', function (Blueprint $table) {
            $table->date('data_emissao')->nullable()->after('valor_total');
            $table->index(['empresa_id', 'data_emissao']);
        });
    }

    public function down(): void
    {
        Schema::table('nfces', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'data_emissao']);
            $table->dropColumn('data_emissao');
        });

        Schema::table('documentos_comerciais', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'data_competencia']);
            $table->dropColumn('data_competencia');
        });
    }
};
