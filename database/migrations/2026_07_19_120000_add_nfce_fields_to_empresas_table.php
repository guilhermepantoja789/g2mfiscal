<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('inscricao_estadual', 20)->nullable()->after('inscricao_municipal');
            $table->unsignedTinyInteger('crt')->nullable()->after('inscricao_estadual')->comment('1=Simples, 2=Simples excesso, 3=Normal');
            $table->unsignedSmallInteger('nfce_serie')->default(1)->after('crt');
            $table->unsignedInteger('nfce_ultimo_numero')->default(0)->after('nfce_serie');
            $table->string('nfce_csc_id', 10)->nullable()->after('nfce_ultimo_numero');
            $table->string('nfce_csc_token', 64)->nullable()->after('nfce_csc_id');
            $table->unsignedTinyInteger('nfce_ambiente')->default(2)->after('nfce_csc_token')->comment('1=producao, 2=homologacao');
            $table->boolean('nfce_contingencia')->default(false)->after('nfce_ambiente');
            $table->string('nfce_contingencia_motivo', 255)->nullable()->after('nfce_contingencia');
            $table->timestamp('nfce_contingencia_desde')->nullable()->after('nfce_contingencia_motivo');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'inscricao_estadual',
                'crt',
                'nfce_serie',
                'nfce_ultimo_numero',
                'nfce_csc_id',
                'nfce_csc_token',
                'nfce_ambiente',
                'nfce_contingencia',
                'nfce_contingencia_motivo',
                'nfce_contingencia_desde',
            ]);
        });
    }
};
