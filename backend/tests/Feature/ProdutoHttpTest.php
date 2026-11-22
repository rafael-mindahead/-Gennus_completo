<?php
namespace Tests\Feature;

use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutoHttpTest extends TestCase
{
    use RefreshDatabase;

    private function autenticar(): void
    {
        $usuario = User::create([
            'name' => 'Administrador Teste',
            'email' => 'admin@gennus.test',
            'password' => 'SenhaSegura123'
        ]);

        $this->actingAs($usuario);
    }

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

    public function test_lista_produtos_autenticado(): void
    {
        $this->autenticar();

        $this->criarProduto('Mouse');
        $this->criarProduto('Teclado');

        $this->getJson('/php/produto_get.php')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nome', 'Mouse');
    }

    public function test_busca_produto_por_id(): void
    {
        $this->autenticar();

        $produto = $this->criarProduto('Monitor');

        $this->getJson(
            '/php/produto_get.php?id=' . $produto->id
        )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Monitor');
    }

    public function test_rejeita_usuario_nao_autenticado(): void
    {
        $this->getJson('/php/produto_get.php')
            ->assertUnauthorized();
    }

    public function test_retorna_404_para_produto_inexistente(): void
    {
        $this->autenticar();

        $this->getJson('/php/produto_get.php?id=999999')
            ->assertNotFound()
            ->assertJsonPath('status', 'nok');
    }

    public function test_rejeita_id_invalido(): void
    {
        $this->autenticar();

        $this->getJson('/php/produto_get.php?id=abc')
            ->assertUnprocessable()
            ->assertJsonPath('status', 'nok');
    }
}
