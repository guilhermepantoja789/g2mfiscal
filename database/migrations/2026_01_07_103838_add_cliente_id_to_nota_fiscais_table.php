<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nota_fiscais', function (Blueprint $table) {
            // Adiciona a coluna cliente_id (nullable por enquanto pra não quebrar dados antigos se existissem em prod)
            $table->foreignId('cliente_id')->nullable()->after('empresa_id')->constrained('clientes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nota_fiscais', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropColumn('cliente_id');
        });
    }
};
