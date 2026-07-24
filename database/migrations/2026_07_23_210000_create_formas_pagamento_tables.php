<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formas_pagamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo', 10); // tPag: 01|03|04|17|...
            $table->string('nome');
            $table->boolean('ativo')->default(true);
            $table->string('tipo_liquidacao', 20)->default('avista'); // avista|prazo
            $table->unsignedInteger('dias_recebimento')->default(0);
            $table->unsignedInteger('parcelas')->default(1);
            $table->decimal('juros_percentual', 8, 4)->default(0);
            $table->boolean('gera_lancamento')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'ativo']);
        });

        Schema::table('documentos_comerciais', function (Blueprint $table) {
            $table->foreignId('forma_pagamento_id')
                ->nullable()
                ->after('forma_pagamento')
                ->constrained('formas_pagamento')
                ->nullOnDelete();
        });

        Schema::table('lancamentos_financeiros', function (Blueprint $table) {
            $table->foreignId('forma_pagamento_id')
                ->nullable()
                ->after('cobranca_id')
                ->constrained('formas_pagamento')
                ->nullOnDelete();
            $table->unsignedInteger('parcela')->nullable()->after('forma_pagamento_id');
            $table->unsignedInteger('total_parcelas')->nullable()->after('parcela');
        });
    }

    public function down(): void
    {
        Schema::table('lancamentos_financeiros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('forma_pagamento_id');
            $table->dropColumn(['parcela', 'total_parcelas']);
        });

        Schema::table('documentos_comerciais', function (Blueprint $table) {
            $table->dropConstrainedForeignId('forma_pagamento_id');
        });

        Schema::dropIfExists('formas_pagamento');
    }
};
