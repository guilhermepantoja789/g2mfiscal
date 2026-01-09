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
        Schema::create('tributacao_nacionals', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 6)->unique(); // Ex: 010101
            $table->string('descricao', 500);       // Ex: Análise e desenvolvimento de sistemas
            $table->string('item_lc116', 10);       // Ex: 1.01
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tributacao_nacionals');
    }
};
