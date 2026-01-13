<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('nota_fiscais', function (Blueprint $table) {
            // Torna os campos antigos nuláveis ou com valor padrão 0
            // Usamos MODIFY ou CHANGE via raw SQL para garantir compatibilidade sem instalar pacotes extras

            // 1. Alíquota ISS
            if (Schema::hasColumn('nota_fiscais', 'aliquota_iss')) {
                $table->decimal('aliquota_iss', 5, 2)->default(0)->nullable()->change();
            } else {
                $table->decimal('aliquota_iss', 5, 2)->default(0)->nullable();
            }

            // 2. Valor ISS
            if (Schema::hasColumn('nota_fiscais', 'valor_iss')) {
                $table->decimal('valor_iss', 15, 2)->default(0)->nullable()->change();
            } else {
                $table->decimal('valor_iss', 15, 2)->default(0)->nullable();
            }

            // 3. Valor Líquido
            if (Schema::hasColumn('nota_fiscais', 'valor_liquido')) {
                $table->decimal('valor_liquido', 15, 2)->default(0)->nullable()->change();
            } else {
                $table->decimal('valor_liquido', 15, 2)->default(0)->nullable();
            }

            // 4. Garantir que servico_id aceite nulo (caso tenha sido criado errado antes)
            if (Schema::hasColumn('nota_fiscais', 'servico_id')) {
                // $table->unsignedBigInteger('servico_id')->nullable()->change();
                // Nota: Mudar chave estrangeira requer cuidados, vamos focar nos valores decimais primeiro.
            }
        });
    }

    public function down()
    {
        // Não é necessário reverter para "obrigatório", pois isso quebraria dados existentes.
    }
};
