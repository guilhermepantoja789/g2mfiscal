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
        Schema::create('nota_fiscais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');

            // Status do Processo
            // status: rascunho, processando, autorizada, erro, cancelada
            $table->string('status')->default('rascunho')->index();

            // Dados de Retorno (Preenchidos só depois da API)
            $table->string('numero_nfse')->nullable(); // Número oficial da nota
            $table->string('codigo_verificacao')->nullable(); // Chave de acesso
            $table->string('link_pdf')->nullable(); // URL do PDF
            $table->text('mensagem_erro')->nullable(); // Se der erro, salvamos aqui

            // Dados do Tomador (Cliente)
            $table->string('tomador_cnpj', 14);
            $table->string('tomador_nome');
            $table->string('tomador_email')->nullable();

            // Valores
            $table->decimal('valor_servico', 10, 2);
            $table->decimal('aliquota_iss', 5, 2); // Ex: 5.00
            $table->decimal('valor_iss', 10, 2);
            $table->decimal('valor_liquido', 10, 2);

            // Detalhes
            $table->text('descricao');
            $table->string('codigo_servico')->default('1.05');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nota_fiscals');
    }
};
