<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListarUsuariosHttpTest extends TestCase
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

    public function test_gestor_pode_listar_usuarios(): void
    {
        $gestor = $this->usuario('administrador');
        $this->usuario('operador');

        $this->actingAs($gestor);

        $resposta = $this->getJson(
            '/php/usuarios_get.php'
        )->assertOk()
         ->assertJsonPath('status', 'ok')
         ->assertJsonCount(2, 'data.usuarios');

        foreach ($resposta->json('data.usuarios') as $usuario) {
            $this->assertArrayHasKey('id', $usuario);
            $this->assertArrayHasKey('nome', $usuario);
            $this->assertArrayHasKey('perfil', $usuario);

            $this->assertArrayNotHasKey('password', $usuario);
            $this->assertArrayNotHasKey('cpf_cnpj', $usuario);
        }
    }

    public function test_operador_nao_pode_listar_usuarios(): void
    {
        $this->actingAs(
            $this->usuario('operador')
        );

        $this->getJson(
            '/php/usuarios_get.php'
        )->assertForbidden();
    }

    public function test_visitante_nao_pode_listar_usuarios(): void
    {
        $this->getJson(
            '/php/usuarios_get.php'
        )->assertUnauthorized();
    }
}
