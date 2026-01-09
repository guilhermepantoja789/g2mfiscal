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
        Schema::create('certificados', function (Blueprint $table) {
            $table->id();

            // Vínculo com a Empresa
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');

            // Dados do Arquivo
            $table->string('nome_arquivo'); // Nome interno (hash)
            $table->string('nome_original')->nullable(); // <--- MUDAMOS PARA NULLABLE

            // Segurança
            $table->text('senha'); // <--- MUDAMOS DE 'senha_encriptada' PARA 'senha'

            // Controle de Validade
            $table->dateTime('valido_ate')->nullable(); // <--- MUDAMOS PARA NULLABLE
            $table->boolean('ativo')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificados');
    }
};
