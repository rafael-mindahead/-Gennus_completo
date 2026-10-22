<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Modules\Produtos\Actions\CreateProduto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateProdutoTest extends TestCase
{
    use RefreshDatabase;

    private function dadosValidos(): array
    {
        return [
            'nome' => 'Teclado Mecânico',
            'categoria' => 'Perifericos',
            'unidade' => 'un',
            'custo' => '100.50',
            'preco' => '199.90',
            'estoque' => '25.500'
        ];
    }

    public function test_cria_produto_valido(): void
    {
        $produto = (new CreateProduto())
            ->execute($this->dadosValidos());

        $this->assertInstanceOf(
            Produto::class,
            $produto
        );

        $this->assertDatabaseHas('produtos', [
            'nome' => 'Teclado Mecânico',
            'unidade' => 'un'
        ]);

        $this->assertSame(
            '199.90',
            $produto->preco
        );
    }

    public function test_rejeita_estoque_negativo(): void
    {
        $dados = $this->dadosValidos();
        $dados['estoque'] = '-10';

        $this->expectException(
            ValidationException::class
        );

        (new CreateProduto())->execute($dados);
    }

    public function test_rejeita_precisao_excessiva(): void
    {
        $dados = $this->dadosValidos();
        $dados['estoque'] = '10.1234';

        $this->expectException(
            ValidationException::class
        );

        (new CreateProduto())->execute($dados);
    }

    public function test_aceita_estoque_inteiro(): void
    {
        $dados = $this->dadosValidos();
        $dados['estoque'] = '20';

        $produto = (new CreateProduto())
            ->execute($dados);

        $this->assertSame(
            '20.000',
            $produto->fresh()->estoque
        );
    }
}
