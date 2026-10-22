git stash pop<?php

namespace Tests\Feature;

use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProdutoModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabela_produtos_existe(): void
    {
        $this->assertTrue(
            Schema::hasColumns('produtos', [
                'id',
                'nome',
                'categoria',
                'unidade',
                'custo',
                'preco',
                'estoque',
                'created_at',
                'updated_at'
            ])
        );
    }

    public function test_consegue_salvar_produto(): void
    {
        $produto = Produto::create([
            'nome' => 'Mouse Gamer',
            'categoria' => 'Perifericos',
            'unidade' => 'un',
            'custo' => 75.50,
            'preco' => 159.90,
            'estoque' => 10.500
        ]);

        $this->assertNotNull($produto->id);

        $this->assertDatabaseHas('produtos', [
            'nome' => 'Mouse Gamer',
            'unidade' => 'un'
        ]);
    }

    public function test_precisao_decimal(): void
    {
        $produto = Produto::create([
            'nome' => 'Produto Teste',
            'categoria' => null,
            'unidade' => 'kg',
            'custo' => 15.25,
            'preco' => 29.90,
            'estoque' => 3.125
        ]);

        $produto->refresh();

        $this->assertSame('15.25', $produto->custo);
        $this->assertSame('29.90', $produto->preco);
        $this->assertSame('3.125', $produto->estoque);
    }
}
