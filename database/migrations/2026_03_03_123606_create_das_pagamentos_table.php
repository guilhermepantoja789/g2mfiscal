<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('das_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->string('competencia'); // 'YYYY-MM'
            $table->decimal('valor_estimado', 15, 2)->default(0);
            $table->decimal('valor_pago', 15, 2)->nullable();
            $table->date('data_pagamento')->nullable();
            $table->enum('status', ['pendente', 'pago'])->default('pendente');
            $table->timestamps();
            
            // Uma empresa só pode ter um registro de DAS por competência
            $table->unique(['empresa_id', 'competencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('das_pagamentos');
    }
};
