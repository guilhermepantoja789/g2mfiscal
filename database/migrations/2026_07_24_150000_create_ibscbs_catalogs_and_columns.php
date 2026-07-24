<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ind_ops', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 6)->unique();
            $table->string('descricao', 255);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('class_tribs', function (Blueprint $table) {
            $table->id();
            $table->string('cst', 3);
            $table->string('c_class_trib', 6);
            $table->string('descricao', 255);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['cst', 'c_class_trib']);
            $table->index('cst');
        });

        Schema::table('servicos', function (Blueprint $table) {
            $table->string('fin_nfse', 1)->default('0')->after('codigo_nbs');
            $table->string('c_ind_op', 6)->nullable()->after('fin_nfse');
            $table->string('cst_ibscbs', 3)->nullable()->after('c_ind_op');
            $table->string('c_class_trib', 6)->nullable()->after('cst_ibscbs');
        });

        Schema::table('nota_fiscais', function (Blueprint $table) {
            $table->string('fin_nfse', 1)->nullable()->after('tp_ret_issqn');
            $table->string('ind_final', 1)->nullable()->after('fin_nfse');
            $table->string('ind_dest', 1)->nullable()->after('ind_final');
            $table->string('c_ind_op', 6)->nullable()->after('ind_dest');
            $table->string('cst_ibscbs', 3)->nullable()->after('c_ind_op');
            $table->string('c_class_trib', 6)->nullable()->after('cst_ibscbs');
        });
    }

    public function down(): void
    {
        Schema::table('nota_fiscais', function (Blueprint $table) {
            $table->dropColumn([
                'fin_nfse',
                'ind_final',
                'ind_dest',
                'c_ind_op',
                'cst_ibscbs',
                'c_class_trib',
            ]);
        });

        Schema::table('servicos', function (Blueprint $table) {
            $table->dropColumn([
                'fin_nfse',
                'c_ind_op',
                'cst_ibscbs',
                'c_class_trib',
            ]);
        });

        Schema::dropIfExists('class_tribs');
        Schema::dropIfExists('ind_ops');
    }
};
