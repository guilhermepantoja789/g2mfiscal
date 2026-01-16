<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('recorrencias', function (Blueprint $table) {
            $table->id();

            // Vínculos
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
            $table->foreignId('servico_id')->nullable()->constrained()->onDelete('set null'); // Opcional, pois pode personalizar

            // Controle da Recorrência
            $table->string('descricao_recorrencia')->nullable(); // Ex: "Contrato Manutenção Mensal" para identificar na lista
            $table->enum('frequencia', ['mensal', 'semanal', 'anual', 'unico'])->default('mensal');
            $table->date('proxima_execucao'); // A data que o sistema vai olhar para gerar
            $table->date('data_fim')->nullable(); // Se null, é infinito
            $table->boolean('ativo')->default(true);
            $table->boolean('emitir_automaticamente')->default(false); // <--- A OPÇÃO QUE DEFINIMOS

            // --- DADOS DA NOTA (Espelho da tabela nota_fiscais) ---

            // Dados Tomador (Snapshot para caso o cliente mude depois, manter o contrato)
            $table->string('tomador_cnpj', 14);
            $table->string('tomador_nome');
            $table->string('tomador_email')->nullable();

            // Valores e Serviço
            $table->decimal('valor_servico', 10, 2);
            $table->text('descricao_servico'); // Aqui aceitará as tags {MES}, {ANO}

            // Fiscal (Configuração salva para esta recorrência)
            $table->integer('trib_issqn'); // 1, 2, 3...
            $table->integer('tp_ret_issqn'); // 1, 2, 3

            // Impostos Aproximados (Percentuais salvos)
            $table->decimal('p_tot_trib_fed', 5, 2)->default(0);
            $table->decimal('p_tot_trib_est', 5, 2)->default(0);
            $table->decimal('p_tot_trib_mun', 5, 2)->default(0); // Alíquota ISS

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recorrencias');
    }
};
