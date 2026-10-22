<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();

            $table->string('nome', 120);
            $table->string('categoria', 120)->nullable();
            $table->string('unidade', 20);

            $table->decimal('custo',12,2 );
            $table->decimal('preco', 12,2);
            $table->decimal('estoque', 12,3)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
