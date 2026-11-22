<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Modules\Produtos\Actions\GetProduto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetProdutoTest extends TestCase
{
    use RefreshDatabase;

    private function criarProduto(string $nome): Produto
    {
        return Produto::create([
            'nome' => $nome,
            'categoria' => 'Perifericos',
            'unidade' => 'un',
            'custo' => '25.50',
            'preco' => '49.90',
            'estoque' => '10.000'
        ]);
    }

    public function test_lista_produtos(): void
    {
        $primeiro = $this->criarProduto('Mouse');
        $segundo = $this->criarProduto('Teclado');

        $produtos = (new GetProduto())->execute();

        $this->assertCount(2, $produtos);

        $this->assertSame(
            [$primeiro->id, $segundo->id],
            $produtos->pluck('id')->all()
        );
    }

    public function test_busca_por_id(): void
    {
        $produto = $this->criarProduto('Monitor');

        $resultado = (new GetProduto())
            ->execute($produto->id);

        $this->assertCount(1, $resultado);

        $this->assertEquals(
            $produto->id,
            $resultado->first()->id
        );
    }

    public function test_lista_vazia(): void
    {
        $produtos = (new GetProduto())->execute();

        $this->assertCount(0, $produtos);
    }

    public function test_produto_inexistente(): void
    {
        $this->expectException(
            ModelNotFoundException::class
        );

        (new GetProduto())->execute(999999);
    }
}
