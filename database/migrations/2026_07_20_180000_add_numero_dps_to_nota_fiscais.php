<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nota_fiscais', function (Blueprint $table) {
            $table->unsignedBigInteger('numero_dps')->nullable()->after('numero_nfse')
                ->comment('Número da DPS enviado à SEFIN (único por série/CNPJ/município)');
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->unsignedBigInteger('nfse_dps_ultimo_numero')->default(0)->after('nfce_ultimo_numero');
        });
    }

    public function down(): void
    {
        Schema::table('nota_fiscais', function (Blueprint $table) {
            $table->dropColumn('numero_dps');
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('nfse_dps_ultimo_numero');
        });
    }
};
