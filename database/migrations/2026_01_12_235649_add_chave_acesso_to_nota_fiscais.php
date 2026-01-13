<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('nota_fiscais', function (Blueprint $table) {
            // Adiciona chave_acesso se não existir
            if (!Schema::hasColumn('nota_fiscais', 'chave_acesso')) {
                $table->string('chave_acesso', 50)->nullable()->after('codigo_verificacao');
            }

            // Aproveita para garantir que xml_autorizado exista
            if (!Schema::hasColumn('nota_fiscais', 'xml_autorizado')) {
                $table->longText('xml_autorizado')->nullable()->after('link_pdf');
            }
        });
    }

    public function down()
    {
        Schema::table('nota_fiscais', function (Blueprint $table) {
            $table->dropColumn(['chave_acesso']);
        });
    }
};
