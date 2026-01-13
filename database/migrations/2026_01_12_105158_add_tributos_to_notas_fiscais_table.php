<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('nota_fiscais', function (Blueprint $table) {
            // Parâmetros ISSQN
            $table->tinyInteger('trib_issqn')->default(1)->comment('1-Tributável, 2-Imunidade, 3-Exportação, 4-Não Incidência');
            $table->tinyInteger('tp_ret_issqn')->default(1)->comment('1-Não Retido, 2-Tomador, 3-Intermediário');

            // Tributos Federais
            $table->decimal('p_tot_trib_fed', 5, 2)->nullable()->comment('Porcentagem Trib Federal');
            $table->decimal('v_tot_trib_fed', 15, 2)->nullable()->comment('Valor Trib Federal');

            // Tributos Estaduais
            $table->decimal('p_tot_trib_est', 5, 2)->nullable();
            $table->decimal('v_tot_trib_est', 15, 2)->nullable();

            // Tributos Municipais
            $table->decimal('p_tot_trib_mun', 5, 2)->nullable();
            $table->decimal('v_tot_trib_mun', 15, 2)->nullable();
        });
    }

    public function down()
    {
        Schema::table('nota_fiscais', function (Blueprint $table) {
            $table->dropColumn([
                'trib_issqn', 'tp_ret_issqn',
                'p_tot_trib_fed', 'v_tot_trib_fed',
                'p_tot_trib_est', 'v_tot_trib_est',
                'p_tot_trib_mun', 'v_tot_trib_mun'
            ]);
        });
    }
};
