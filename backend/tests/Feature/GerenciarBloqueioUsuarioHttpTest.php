<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GerenciarBloqueioUsuarioHttpTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(
        string $perfil,
        string $email
    ): User {
        $usuario = User::create([
            'name' => ucfirst($perfil),
            'email' => $email,
            'password' => 'SenhaSegura123',
        ]);

        $usuario->forceFill([
            'perfil' => $perfil,
        ])->save();

        return $usuario->fresh();
    }

    public function test_gestor_bloqueia_operador(): void
    {
        $gestor = $this->usuario(
            'administrador',
            'gestor@gennus.test'
        );

        $operador = $this->usuario(
            'operador',
            'operador@gennus.test'
        );

        $this->actingAs($gestor);
        

        $this->postJson('/php/usuario_bloquear.php', [
            'usuario_id' => $operador->id,
        ])->assertOk();

        $this->assertNotNull(
            $operador->fresh()->bloqueado_em
        );

        $this->assertDatabaseHas('usuario_auditorias', [
            'gestor_id' => $gestor->id,
            'usuario_id' => $operador->id,
            'acao' => 'bloqueio',
        ]);

        // Confirma que a conta bloqueada
        // não consegue continuar utilizando o ERP.
        $this->actingAs($operador);

        $this->getJson('/php/produto_get.php')
            ->assertUnauthorized();
    }

    public function test_gestor_desbloqueia_operador(): void
    {
        $gestor = $this->usuario(
            'administrador',
            'gestor@gennus.test'
        );

        $operador = $this->usuario(
            'operador',
            'operador@gennus.test'
        );

        $operador->forceFill([
            'bloqueado_em' => now(),
        ])->save();

        $this->actingAs($gestor);

        $this->postJson('/php/usuario_desbloquear.php', [
            'usuario_id' => $operador->id,
        ])->assertOk();

        $this->assertNull(
            $operador->fresh()->bloqueado_em
        );

        $this->assertDatabaseHas('usuario_auditorias', [
            'gestor_id' => $gestor->id,
            'usuario_id' => $operador->id,
            'acao' => 'desbloqueio',
        ]);
    }

    public function test_operador_nao_pode_bloquear(): void
    {
        $operador = $this->usuario(
            'operador',
            'operador@gennus.test'
        );

        $outro = $this->usuario(
            'leitor',
            'leitor@gennus.test'
        );

        $this->actingAs($operador);

        $this->postJson('/php/usuario_bloquear.php', [
            'usuario_id' => $outro->id,
        ])->assertForbidden();

        $this->assertNull(
            $outro->fresh()->bloqueado_em
        );
    }

    public function test_gestor_nao_pode_se_bloquear(): void
    {
        $gestor = $this->usuario(
            'administrador',
            'gestor@gennus.test'
        );

        $this->actingAs($gestor);

        $this->postJson('/php/usuario_bloquear.php', [
            'usuario_id' => $gestor->id,
        ])->assertUnprocessable();

        $this->assertNull(
            $gestor->fresh()->bloqueado_em
        );
    }

    public function test_visitante_nao_pode_bloquear(): void
    {
        $operador = $this->usuario(
            'operador',
            'operador@gennus.test'
        );

        $this->postJson('/php/usuario_bloquear.php', [
            'usuario_id' => $operador->id,
        ])->assertUnauthorized();
    }
}
