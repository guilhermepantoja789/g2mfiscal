<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formas_pagamento', function (Blueprint $table) {
            $table->foreignId('conta_contabil_id')
                ->nullable()
                ->after('gera_lancamento')
                ->constrained('contas_contabeis')
                ->nullOnDelete();
        });

        Schema::create('documento_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_comercial_id')
                ->constrained('documentos_comerciais')
                ->cascadeOnDelete();
            $table->foreignId('forma_pagamento_id')
                ->constrained('formas_pagamento')
                ->restrictOnDelete();
            $table->decimal('valor', 15, 2);
            $table->decimal('v_troco', 15, 2)->nullable();
            $table->unsignedSmallInteger('ordem')->default(1);
            $table->timestamps();

            $table->index(['documento_comercial_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_pagamentos');

        Schema::table('formas_pagamento', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conta_contabil_id');
        });
    }
};
