<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('recorrencias', function (Blueprint $table) {
            // Campos de endereço do tomador
            $table->string('tomador_telefone')->nullable()->after('tomador_email');
            $table->string('tomador_im')->nullable()->after('tomador_telefone');
            $table->string('tomador_cep', 10)->nullable()->after('tomador_im');
            $table->string('tomador_endereco')->nullable()->after('tomador_cep');
            $table->string('tomador_numero', 20)->nullable()->after('tomador_endereco');
            $table->string('tomador_complemento')->nullable()->after('tomador_numero');
            $table->string('tomador_bairro')->nullable()->after('tomador_complemento');
            $table->string('tomador_cidade', 7)->nullable()->after('tomador_bairro'); // Codigo IBGE
            $table->string('tomador_uf', 2)->nullable()->after('tomador_cidade');
        });
    }

    public function down()
    {
        Schema::table('recorrencias', function (Blueprint $table) {
            $table->dropColumn([
                'tomador_telefone', 'tomador_im', 'tomador_cep',
                'tomador_endereco', 'tomador_numero', 'tomador_complemento',
                'tomador_bairro', 'tomador_cidade', 'tomador_uf'
            ]);
        });
    }
};
