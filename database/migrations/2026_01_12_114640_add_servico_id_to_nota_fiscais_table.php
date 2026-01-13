<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('nota_fiscais', function (Blueprint $table) {
            // Adiciona a coluna servico_id, que pode ser nula (nullable)
            // e cria a chave estrangeira ligando com a tabela servicos
            $table->foreignId('servico_id')
                ->nullable()
                ->after('cliente_id') // Para ficar organizado
                ->constrained('servicos')
                ->onDelete('set null'); // Se apagar o serviço, não apaga a nota
        });
    }

    public function down()
    {
        Schema::table('nota_fiscais', function (Blueprint $table) {
            $table->dropForeign(['servico_id']);
            $table->dropColumn('servico_id');
        });
    }
};
