<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Alguns tomadores exigem IM
            $table->string('inscricao_municipal', 20)->nullable()->after('cnpj');
            // Complemento de endereço (era o único que faltava)
            $table->string('complemento')->nullable()->after('numero');
            // Contato para envio
            $table->string('telefone', 20)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['inscricao_municipal', 'complemento', 'telefone']);
        });
    }
};
