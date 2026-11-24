<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Modules\Produtos\Actions\DeleteProduto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class DeleteProdutoTest extends TestCase
{
    use RefreshDatabase;

    private function criarProduto(string $nome = 'Mouse'): Produto
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

    public function test_exclui_produto_logicamente(): void
    {
        $produto = $this->criarProduto();

        (new DeleteProduto())->execute($produto->id, $this->$operador);

        $this->assertSoftDeleted('produtos', [
            'id' => $produto->id
        ]);

        $this->assertNull(
            Produto::find($produto->id)
        );
    }

    public function test_preserva_registro_no_banco(): void
    {
        $produto = $this->criarProduto();

        (new DeleteProduto())->execute($produto->id, $this->$operador);

        $arquivado = Produto::withTrashed()
            ->find($produto->id);

        $this->assertNotNull($arquivado);
        $this->assertSame('Mouse', $arquivado->nome);
        $this->assertSame('10.000', $arquivado->estoque);
    }

    public function test_nao_exclui_outros_produtos(): void
    {
        $primeiro = $this->criarProduto('Mouse');
        $segundo = $this->criarProduto('Teclado');

        (new DeleteProduto())->execute($primeiro->id, $this->$operador);

        $this->assertNotNull(
            Produto::find($segundo->id)
        );

        $this->assertNull(
            Produto::find($primeiro->id)
        );
    }

    public function test_produto_inexistente(): void
    {
        $this->expectException(
            ModelNotFoundException::class
        );

        (new DeleteProduto())->execute(999999);
    }
    private User $operador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operador = User::create([
            'name' => 'Operador Teste',
            'email' => 'operador@gennus.test',
            'password' => 'SenhaSegura123',
        ]);

        $this->operador->forceFill([
            'perfil' => 'operador',
        ])->save();
    }
}
