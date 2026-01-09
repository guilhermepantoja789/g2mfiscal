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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');

            $table->string('razao_social');
            $table->string('cnpj', 14); // Vamos focar em PJ por enquanto
            $table->string('email')->nullable();

            // Endereço (Importante salvar aqui para reusar)
            $table->string('cep', 8)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero')->nullable();
            $table->string('bairro')->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('cidade_codigo', 7)->nullable(); // IBGE

            $table->timestamps();

            // Garante que não duplique CNPJ dentro da MESMA empresa
            $table->unique(['empresa_id', 'cnpj']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
