<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobrancas', function (Blueprint $table) {
            $table->id();

            // Vínculos
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('cliente_id')->constrained('clientes');
            // O vínculo com a nota é anulável, pois no futuro você pode querer lançar uma cobrança avulsa
            $table->foreignId('nota_fiscal_id')->nullable()->constrained('nota_fiscais')->onDelete('cascade');

            // Identificadores Externos (Para quando conectar a API)
            $table->string('gateway')->default('asaas'); // asaas, iugu, etc
            $table->string('external_id')->nullable()->index(); // ID da cobrança no banco (pay_123)

            // Dados Financeiros
            $table->decimal('valor', 10, 2);
            $table->decimal('valor_liquido', 10, 2)->nullable(); // Valor descontando taxas
            $table->date('vencimento');
            $table->string('status')->default('PENDING'); // PENDING, RECEIVED, OVERDUE, CANCELLED

            // Dados de Pagamento (Links)
            $table->string('link_boleto')->nullable();
            $table->text('pix_qrcode')->nullable();
            $table->string('pix_imagem')->nullable();

            $table->text('descricao')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Importante para histórico
        });

        // Vamos adicionar o campo de configuração financeira na empresa
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('asaas_wallet_id')->nullable()->comment('ID da subconta no Asaas');
            $table->string('asaas_token')->nullable()->comment('API Key específica da subconta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobrancas');

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['asaas_wallet_id', 'asaas_token']);
        });
    }
};
