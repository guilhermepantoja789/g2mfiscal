<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');

            // Identificação interna
            $table->string('nome', 100); // Ex: "Consultoria Mensal"
            $table->string('codigo_interno')->nullable(); // Ex: "SERV-001"

            // Dados Fiscais (Obrigatórios para DPS)
            // cTribNac: Ex "010601"
            $table->string('codigo_tributacao_nacional', 10);
            // cTribMun: Ex "100" ou "1061" (Opcional, mas bom ter)
            $table->string('codigo_tributacao_municipal', 20)->nullable();

            // NBS (Nomenclatura Brasileira de Serviços) - Opcional mas futuro
            $table->string('codigo_nbs', 10)->nullable();

            // Descrição Padrão (xDescServ)
            $table->text('descricao');

            // Valores
            $table->decimal('valor_unitario', 10, 2)->default(0);

            // Configurações de Imposto (Para quem NÃO é Simples ou tem retenção)
            $table->boolean('iss_retido')->default(false);
            $table->decimal('aliquota_iss', 5, 2)->default(0.00);
            $table->decimal('aliquota_pis', 5, 2)->default(0.00);
            $table->decimal('aliquota_cofins', 5, 2)->default(0.00);
            $table->decimal('aliquota_inss', 5, 2)->default(0.00);
            $table->decimal('aliquota_ir', 5, 2)->default(0.00);
            $table->decimal('aliquota_csll', 5, 2)->default(0.00);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicos');
    }
};
