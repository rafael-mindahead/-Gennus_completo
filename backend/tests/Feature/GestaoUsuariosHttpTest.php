<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestaoUsuariosHttpTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $perfil): User
    {
        $usuario = User::create([
            'name' => ucfirst($perfil),
            'email' => $perfil . '@gennus.test',
            'password' => 'SenhaSegura123',
        ]);

        $usuario->forceFill([
            'perfil' => $perfil,
        ])->save();

        return $usuario->fresh();
    }

    private function dados(string $perfil): array
    {
        return [
            'nome' => 'Funcionario',
            'sobrenome' => 'Teste',
            'email_corporativo' => 'funcionario@gennus.test',
            'senha' => 'SenhaSegura123',
            'perfil' => $perfil,
        ];
    }

    public function test_gestor_cadastra_operador(): void
    {
        $this->actingAs($this->usuario('administrador'));

        $this->postJson(
            '/php/usuario_novo.php',
            $this->dados('operador')
        )->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'funcionario@gennus.test',
            'perfil' => 'operador',
        ]);
    }

    public function test_gestor_cadastra_leitor(): void
    {
        $this->actingAs($this->usuario('administrador'));

        $this->postJson(
            '/php/usuario_novo.php',
            $this->dados('leitor')
        )->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'funcionario@gennus.test',
            'perfil' => 'leitor',
        ]);
    }

    public function test_gestor_nao_cria_outro_administrador(): void
    {
        $this->actingAs($this->usuario('administrador'));

        $this->postJson(
            '/php/usuario_novo.php',
            $this->dados('administrador')
        )->assertUnprocessable();

        $this->assertDatabaseMissing('users', [
            'email' => 'funcionario@gennus.test',
        ]);
    }

    public function test_operador_e_leitor_nao_cadastram(): void
    {
        foreach (['operador', 'leitor'] as $perfil) {
            $this->actingAs($this->usuario($perfil));

            $this->postJson(
                '/php/usuario_novo.php',
                $this->dados('operador')
            )->assertForbidden();
        }

        $this->assertDatabaseMissing('users', [
            'email' => 'funcionario@gennus.test',
        ]);
    }

    public function test_visitante_nao_cadastra(): void
    {
        $this->postJson(
            '/php/usuario_novo.php',
            $this->dados('leitor')
        )->assertUnauthorized();
    }
}
