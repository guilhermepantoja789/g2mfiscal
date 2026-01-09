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
        Schema::table('nota_fiscais', function (Blueprint $table) {
            // Para armazenar o XML final da NFSe (importante para o cliente)
            $table->longText('xml_autorizado')->nullable()->after('link_pdf');

            // Para armazenar o XML de envio (importante para debug)
            $table->longText('xml_enviado')->nullable()->after('xml_autorizado');

            // Campos extras para controle
            $table->string('ambiente', 20)->default('homologacao'); // producao ou homologacao
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('nota_fiscais', function (Blueprint $table) {
            $table->dropColumn(['xml_autorizado', 'xml_enviado', 'ambiente']);
        });
    }
};
