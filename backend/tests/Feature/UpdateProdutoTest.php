<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Modules\Produtos\Actions\UpdateProduto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateProdutoTest extends TestCase
{
    use RefreshDatabase;

    private function criarProduto(): Produto
    {
        return Produto::create([
            'nome' => 'Mouse',
            'categoria' => 'Perifericos',
            'unidade' => 'un',
            'custo' => '25.50',
            'preco' => '49.90',
            'estoque' => '10.000'
        ]);
    }

    private function dadosAtualizados(): array
    {
        return [
            'nome' => 'Mouse Sem Fio',
            'categoria' => 'Perifericos',
            'unidade' => 'un',
            'custo' => '45.10',
            'preco' => '89.90',
            'estoque' => '12.500'
        ];
    }

    public function test_atualiza_produto(): void
    {
        $produto = $this->criarProduto();

        $resultado = (new UpdateProduto())->execute(
            $produto->id,
            $this->dadosAtualizados()
        );

        $this->assertSame('Mouse Sem Fio', $resultado->nome);
        $this->assertSame('89.90', $resultado->preco);

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'nome' => 'Mouse Sem Fio'
        ]);
    }

    public function test_rejeita_estoque_negativo(): void
    {
        $produto = $this->criarProduto();
        $dados = $this->dadosAtualizados();
        $dados['estoque'] = '-5';

        $this->expectException(ValidationException::class);

        (new UpdateProduto())->execute($produto->id, $dados);
    }

    public function test_rejeita_preco_com_precisao_invalida(): void
    {
        $produto = $this->criarProduto();
        $dados = $this->dadosAtualizados();
        $dados['preco'] = '99.999';

        $this->expectException(ValidationException::class);

        (new UpdateProduto())->execute($produto->id, $dados);
    }

    public function test_produto_inexistente(): void
    {
        $this->expectException(ModelNotFoundException::class);

        (new UpdateProduto())->execute(
            999999,
            $this->dadosAtualizados()
        );
    }
}
