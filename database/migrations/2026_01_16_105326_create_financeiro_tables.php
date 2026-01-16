<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela de Saques / Transferências
        Schema::create('transferencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');

            $table->decimal('valor', 10, 2);
            $table->decimal('taxa', 10, 2)->default(0); // Taxa de saque (ex: 2.00)
            $table->string('status')->default('PROCESSING'); // PROCESSING, DONE, FAILED

            // Dados de destino (Snapshot do momento do saque)
            $table->string('banco_destino')->nullable();
            $table->string('agencia_destino')->nullable();
            $table->string('conta_destino')->nullable();
            $table->string('chave_pix_destino')->nullable();

            // ID no Asaas
            $table->string('external_id')->nullable();

            $table->timestamp('data_solicitacao');
            $table->timestamp('data_liquidacao')->nullable();

            $table->timestamps();
        });

        // 2. Adicionar Configurações Bancárias na Tabela Empresas
        Schema::table('empresas', function (Blueprint $table) {
            // Conta Bancária "Real" do Cliente (Para onde vai o dinheiro)
            $table->string('banco_codigo')->nullable(); // Ex: 341
            $table->string('banco_nome')->nullable();   // Ex: Itaú
            $table->string('agencia', 10)->nullable();
            $table->string('conta', 20)->nullable();
            $table->string('conta_tipo', 10)->default('CC'); // CC ou CP
            $table->string('chave_pix')->nullable(); // Preferencial para saque

            // Configurações de Automação
            $table->boolean('saque_automatico')->default(false);
            $table->integer('saque_frequencia_dias')->default(1); // 1 = Diario, 7 = Semanal
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias');
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'banco_codigo', 'banco_nome', 'agencia', 'conta', 'conta_tipo',
                'chave_pix', 'saque_automatico', 'saque_frequencia_dias'
            ]);
        });
    }
};
