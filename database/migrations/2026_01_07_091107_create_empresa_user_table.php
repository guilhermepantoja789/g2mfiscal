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
        Schema::create('empresa_user', function (Blueprint $table) {
            $table->id();

            // Chaves Estrangeiras
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');

            // Permissões (ex: 'admin', 'contador', 'operador')
            $table->string('perfil')->default('admin');

            $table->timestamps();

            // Evita duplicidade (o mesmo usuário na mesma empresa 2x)
            $table->unique(['user_id', 'empresa_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa_user');
    }
};
