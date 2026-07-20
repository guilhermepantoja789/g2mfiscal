<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('chave', 44)->nullable()->unique();
            $table->string('protocolo', 20)->nullable();
            $table->unsignedInteger('numero');
            $table->unsignedSmallInteger('serie');
            $table->unsignedTinyInteger('ambiente')->comment('1=producao, 2=homologacao');
            $table->unsignedTinyInteger('tp_emis')->default(1);
            $table->string('status', 30)->default('processando');
            $table->string('c_stat', 10)->nullable();
            $table->string('x_motivo', 500)->nullable();
            $table->longText('xml_enviado')->nullable();
            $table->longText('xml_autorizado')->nullable();
            $table->text('qr_code_url')->nullable();
            $table->json('payload')->nullable();
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->string('destinatario_doc', 14)->nullable();
            $table->string('destinatario_nome', 120)->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'serie', 'numero', 'ambiente']);
            $table->index(['empresa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfces');
    }
};
