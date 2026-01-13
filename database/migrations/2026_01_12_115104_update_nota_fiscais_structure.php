<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('nota_fiscais', function (Blueprint $table) {

            // 1. Coluna Emissão (Data de Competência)
            if (!Schema::hasColumn('nota_fiscais', 'emissao')) {
                $table->dateTime('emissao')->nullable()->after('descricao');
            }

            // 2. Campos da DPS (Situação e Retenção)
            if (!Schema::hasColumn('nota_fiscais', 'trib_issqn')) {
                $table->tinyInteger('trib_issqn')->default(1)->comment('1-Normal, 2-Imune...');
            }
            if (!Schema::hasColumn('nota_fiscais', 'tp_ret_issqn')) {
                $table->tinyInteger('tp_ret_issqn')->default(1)->comment('1-Sem Retenção, 2-Retido...');
            }

            // 3. Valores Aproximados (Lei da Transparência) - R$
            if (!Schema::hasColumn('nota_fiscais', 'v_tot_trib_fed')) {
                $table->decimal('v_tot_trib_fed', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('nota_fiscais', 'v_tot_trib_est')) {
                $table->decimal('v_tot_trib_est', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('nota_fiscais', 'v_tot_trib_mun')) {
                $table->decimal('v_tot_trib_mun', 15, 2)->default(0);
            }

            // 4. Porcentagens Aproximadas - %
            if (!Schema::hasColumn('nota_fiscais', 'p_tot_trib_fed')) {
                $table->decimal('p_tot_trib_fed', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('nota_fiscais', 'p_tot_trib_est')) {
                $table->decimal('p_tot_trib_est', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('nota_fiscais', 'p_tot_trib_mun')) {
                $table->decimal('p_tot_trib_mun', 5, 2)->default(0);
            }
        });
    }

    public function down()
    {
        // Remove apenas se existirem (opcional, para rollback)
        Schema::table('nota_fiscais', function (Blueprint $table) {
            $columns = [
                'emissao', 'trib_issqn', 'tp_ret_issqn',
                'v_tot_trib_fed', 'v_tot_trib_est', 'v_tot_trib_mun',
                'p_tot_trib_fed', 'p_tot_trib_est', 'p_tot_trib_mun'
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('nota_fiscais', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
