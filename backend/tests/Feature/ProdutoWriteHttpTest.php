<?php
namespace Tests\Feature;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutoWriteHttpTest extends TestCase
{
    use RefreshDatabase;

    private function autenticar(): void
    {
        $usuario = User::create([
            'name' => 'Administrador',
            'email' => 'admin@gennus.test',
            'password' => 'SenhaSegura123'
        ]);

        $this->actingAs($usuario);
    }

    private function dados(): array
    {
        return [
            'nome' => 'Mouse Gamer',
            'categoria' => 'Perifericos',
            'unidade' => 'un',
            'custo' => '50.00',
            'preco' => '99.90',
            'estoque' => '10.000'
        ];
    }

    private function criarProduto(): Produto
    {
        return Produto::create($this->dados());
    }

    public function test_cadastra_produto_http(): void
    {
        $this->autenticar();

        $this->postJson(
            '/php/produto_novo.php',
            $this->dados()
        )
            ->assertCreated()
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('produtos', [
            'nome' => 'Mouse Gamer'
        ]);
    }

    public function test_atualiza_produto_http(): void
    {
        $this->autenticar();
        $produto = $this->criarProduto();

        $dados = $this->dados();
        $dados['nome'] = 'Mouse Atualizado';

        $this->postJson(
            "/php/produto_alterar.php?id={$produto->id}",
            $dados
        )
            ->assertOk()
            ->assertJsonPath(
                'data.nome',
                'Mouse Atualizado'
            );
    }

    public function test_exclui_produto_http(): void
    {
        $this->autenticar();

        $produto = $this->criarProduto();

        $resposta = $this->postJson(
            "/php/produto_excluir.php?id={$produto->id}"
        );

        $resposta
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertSoftDeleted('produtos', [
            'id' => $produto->id
        ]);

        $this->assertNull(
            Produto::find($produto->id)
        );
    }


    public function test_rejeita_usuario_nao_autenticado(): void
    {
        $this->postJson(
            '/php/produto_novo.php',
            $this->dados()
        )->assertUnauthorized();
    }

    public function test_rejeita_estoque_negativo(): void
    {
        $this->autenticar();

        $dados = $this->dados();
        $dados['estoque'] = '-5';

        $this->postJson(
            '/php/produto_novo.php',
            $dados
        )
            ->assertUnprocessable()
            ->assertJsonPath('status', 'nok');
    }

    public function test_rejeita_produto_inexistente(): void
    {
        $this->autenticar();

        $this->postJson(
            '/php/produto_alterar.php?id=999999',
            $this->dados()
        )->assertNotFound();
    }
    public function test_cadastra_produto_com_codigo_barras_http(): void
    {
        $this->autenticar();

        $dados = $this->dados();
        $dados['codigo_barras'] = '07891234567890';

        $this->postJson(
            '/php/produto_novo.php',
            $dados
        )
            ->assertCreated()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath(
                'data.codigo_barras',
                '07891234567890'
            );

        $this->assertDatabaseHas('produtos', [
            'codigo_barras' => '07891234567890'
        ]);
    }
}
