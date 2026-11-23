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

public function test_cadastra_produto_com_codigo_barras(): void
{
    $dados = $this->dadosValidos();

    $dados['codigo_barras'] = '07891234567890';

    $produto = (new CreateProduto())->execute($dados);

    $this->assertSame(
        '07891234567890',
        $produto->fresh()->codigo_barras
    );

    $this->assertDatabaseHas('produtos', [
        'id' => $produto->id,
        'codigo_barras' => '07891234567890'
    ]);
}

public function test_rejeita_codigo_barras_invalido(): void
{
    $dados = $this->dadosValidos();
    $dados['codigo_barras'] = 'ABC123';

    try {
        (new CreateProduto())->execute($dados);

        $this->fail(
            'Deveria rejeitar o código de barras inválido.'
        );

    } catch (ValidationException $e) {
        $this->assertArrayHasKey(
            'codigo_barras',
            $e->errors()
        );

        $this->assertDatabaseCount('produtos', 0);
    }
}
    public function test_rejeita_codigo_barras_duplicado(): void
    {
        $dados = $this->dadosValidos();
        $dados['codigo_barras'] = '7900000000001';

        $action = new CreateProduto();

        $action->execute($dados);

        try {
            $action->execute($dados);

            $this->fail(
                'Deveria rejeitar o código de barras duplicado.'
            );

        } catch (ValidationException $e) {
            $this->assertArrayHasKey(
                'codigo_barras',
                $e->errors()
            );

            $this->assertDatabaseCount('produtos', 1);
        }
    }
}
