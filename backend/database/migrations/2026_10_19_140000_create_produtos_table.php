
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->increments('id');

            $table->string('nome', 150);

            $table->string('categoria', 100)
                ->nullable();

            $table->string('unidade', 30);

            $table->decimal('custo', 12, 2)
                ->default(0);

            $table->decimal('preco', 12, 2)
                ->default(0);

            $table->decimal('estoque', 12, 3)
                ->default(0);

            $table->dateTime('criado_em')
                ->useCurrent();

            $table->dateTime('atualizado_em')
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->index('nome', 'idx_produtos_nome');
            $table->index('categoria', 'idx_produtos_categoria');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
