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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();

            // Identificação
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // O criador/dono principal
            $table->string('cnpj', 14)->unique(); // Apenas números
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('inscricao_municipal')->nullable();

            // Regime Tributário (Códigos numéricos da ABRASF)
            $table->integer('regime_tributario')->default(1);

            // Endereço
            $table->string('cep', 8);
            $table->string('logradouro');
            $table->string('numero');
            $table->string('complemento')->nullable();
            $table->string('bairro');

            // Localização Fiscal
            $table->string('uf', 2); // Ex: AM
            $table->string('cod_ibge_mun', 7); // Ex: 1302603 (Manaus)

            // Contato
            $table->string('email')->nullable();
            $table->string('telefone')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Lixeira lógica
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
