<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nbs_codes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 9)->unique();
            $table->string('descricao', 500);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('nbs_correlacoes', function (Blueprint $table) {
            $table->id();
            $table->string('c_trib_nac', 6);
            $table->string('codigo_nbs', 9);
            $table->string('c_ind_op', 6);
            $table->string('cst', 3);
            $table->string('c_class_trib', 6);
            $table->string('escopo', 20)->default('geral'); // ti_manaus|geral
            $table->timestamps();

            $table->index('c_trib_nac');
            $table->index('codigo_nbs');
            $table->index('escopo');
            $table->unique(
                ['c_trib_nac', 'codigo_nbs', 'c_ind_op', 'c_class_trib'],
                'nbs_correlacoes_unique_combo'
            );
        });

        Schema::table('class_tribs', function (Blueprint $table) {
            $table->boolean('destaque')->default(false)->after('ativo');
        });
    }

    public function down(): void
    {
        Schema::table('class_tribs', function (Blueprint $table) {
            $table->dropColumn('destaque');
        });

        Schema::dropIfExists('nbs_correlacoes');
        Schema::dropIfExists('nbs_codes');
    }
};
