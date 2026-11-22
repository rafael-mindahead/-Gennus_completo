<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentacoes_estoque', function (Blueprint $table) {
            $table->id();
            $table->uuid('evento_id')->unique();
            $table->foreignId('produto_id')->constrained('produtos')->restrictOnDelete();
            $table->unsignedInteger('dispositivo_id')->nullable();
            $table->string('origem', 20);
            $table->decimal('quantidade_delta', 12, 3);
            $table->decimal('saldo_anterior', 12,3);
            $table->decimal('saldo_posterior', 12,3);
            $table->timestamp('ocorrido_em')->nullable();
            $table->timestamps();
            $table->index(['produto_id', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_estoque');
    }
};
