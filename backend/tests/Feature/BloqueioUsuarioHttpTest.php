<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BloqueioUsuarioHttpTest extends TestCase
{
    use RefreshDatabase;

    private function operador(): User
    {
        $usuario = User::create([
            'name' => 'Operador Teste',
            'email' => 'operador@gennus.test',
            'password' => 'SenhaSegura123',
        ]);

        $usuario->forceFill([
            'perfil' => 'operador',
        ])->save();

        return $usuario->fresh();
    }

    public function test_usuario_bloqueado_nao_consegue_login(): void
    {
        $usuario = $this->operador();

        $usuario->forceFill([
            'bloqueado_em' => now(),
        ])->save();

        $this->postJson('/php/usuario_login.php', [
            'email' => 'operador@gennus.test',
            'senha' => 'SenhaSegura123',
        ])->assertUnauthorized();

        $this->assertGuest();
    }

    public function test_bloqueio_revoga_acesso_de_sessao_ativa(): void
    {
        $usuario = $this->operador();

        $this->actingAs($usuario);

        $this->getJson(
            '/php/valida_sessao.php'
        )->assertOk();

        // Simula o gestor bloqueando a conta no banco.
        $usuario->forceFill([
            'bloqueado_em' => now(),
        ])->save();

        // A sessão existente não deve continuar válida.
        $this->getJson(
            '/php/valida_sessao.php'
        )->assertUnauthorized();

        // Nem poderá consultar produtos.
        $this->getJson(
            '/php/produto_get.php'
        )->assertUnauthorized();
    }

    public function test_usuario_ativo_mantem_acesso(): void
    {
        $usuario = $this->operador();

        $this->actingAs($usuario);

        $this->getJson(
            '/php/valida_sessao.php'
        )->assertOk();

        $this->getJson(
            '/php/produto_get.php'
        )->assertOk();
    }
}
