<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_auditorias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gestor_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('gestor_nome', 120);
            $table->string('usuario_nome', 120);
            $table->string('acao', 30);
            $table->timestamp('ocorrido_em')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_auditorias');
    }
};
