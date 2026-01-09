<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Campos obrigatórios para a DPS / Simples Nacional
            // opSimpNac: 1=Não Optante, 2=MEI, 3=ME/EPP
            // Estamos mudando o default para 1 (Não Optante) por segurança
            $table->tinyInteger('regime_tributario')->default(1)->comment('1:Não Optante, 2:MEI, 3:Simples Nacional')->change();

            // regApTribSN: Obrigatório se regime_tributario = 3
            // 1=Pelo SN (Padrão)
            $table->tinyInteger('regime_apuracao_sn')->default(0)->after('regime_tributario')
                ->comment('1: Pelo SN, 2: Misto (ISS por fora), 0: Não se aplica');

            // regEspTrib: 0=Nenhum (Padrão)
            $table->tinyInteger('regime_especial_tributacao')->default(0)->after('regime_apuracao_sn')
                ->comment('0: Nenhum, 1: Cooperativa, etc.');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['regime_apuracao_sn', 'regime_especial_tributacao']);
        });
    }
};
