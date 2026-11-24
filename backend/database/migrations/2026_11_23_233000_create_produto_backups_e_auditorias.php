<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produto_backups_temporarios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('produto_id')
                ->unique()
                ->constrained('produtos')
                ->restrictOnDelete();

            $table->foreignId('operador_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('operador_nome', 120);
            $table->string('produto_nome', 120);
            $table->json('dados');

            $table->timestamp('criado_em');
            $table->timestamp('expira_em')->index();
        });
        Schema::create('produto_auditorias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('produto_id')
                ->constrained('produtos')
                ->restrictOnDelete();

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('usuario_nome', 120);
            $table->string('produto_nome', 120);
            $table->string('acao', 30);
            $table->timestamp('ocorrido_em')->index();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('produto_auditorias');
        Schema::dropIfExists('produto_backups_temporarios');
    }
};
