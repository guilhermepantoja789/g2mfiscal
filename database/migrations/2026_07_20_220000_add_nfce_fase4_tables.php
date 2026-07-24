<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nfces', function (Blueprint $table) {
            $table->timestamp('cancelado_em')->nullable()->after('destinatario_nome');
            $table->string('protocolo_cancelamento', 20)->nullable()->after('cancelado_em');
            $table->string('motivo_cancelamento', 255)->nullable()->after('protocolo_cancelamento');
            $table->longText('xml_evento_cancelamento')->nullable()->after('motivo_cancelamento');
        });

        Schema::create('nfce_inutilizacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->unsignedSmallInteger('serie');
            $table->unsignedInteger('numero_ini');
            $table->unsignedInteger('numero_fin');
            $table->unsignedTinyInteger('ano')->comment('Ano com 2 dígitos (AA)');
            $table->unsignedTinyInteger('ambiente')->comment('1=producao, 2=homologacao');
            $table->string('protocolo', 20)->nullable();
            $table->string('c_stat', 10)->nullable();
            $table->string('x_motivo', 500)->nullable();
            $table->string('x_just', 255);
            $table->longText('xml_enviado')->nullable();
            $table->longText('xml_retorno')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'serie', 'numero_ini', 'numero_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfce_inutilizacoes');

        Schema::table('nfces', function (Blueprint $table) {
            $table->dropColumn([
                'cancelado_em',
                'protocolo_cancelamento',
                'motivo_cancelamento',
                'xml_evento_cancelamento',
            ]);
        });
    }
};
