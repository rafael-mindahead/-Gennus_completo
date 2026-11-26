<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class GerenciarPerfilUsuarioHttpTest extends TestCase
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

    public function test_gestor_promove_leitor_para_operador(): void
    {
        $gestor = $this->usuario(
            'administrador',
            'gestor@gennus.test'
        );

        $leitor = $this->usuario(
            'leitor',
            'leitor@gennus.test'
        );

        $this->actingAs($gestor)
            ->postJson('/php/usuario_perfil.php', [
                'usuario_id' => $leitor->id,
                'perfil' => 'operador',
            ])->assertOk();

        $this->assertSame(
            'operador',
            $leitor->fresh()->perfil
        );

        $this->assertTrue(
            Gate::forUser($leitor->fresh())
                ->allows('produtos.gravar')
        );

        $this->assertDatabaseHas('usuario_auditorias', [
            'gestor_id' => $gestor->id,
            'usuario_id' => $leitor->id,
            'acao' => 'alteracao_perfil',
            'perfil_anterior' => 'leitor',
            'perfil_novo' => 'operador',
        ]);
    }

    public function test_gestor_rebaixa_operador_para_leitor(): void
    {
        $gestor = $this->usuario(
            'administrador',
            'gestor@gennus.test'
        );

        $operador = $this->usuario(
            'operador',
            'operador@gennus.test'
        );

        $this->actingAs($gestor)
            ->postJson('/php/usuario_perfil.php', [
                'usuario_id' => $operador->id,
                'perfil' => 'leitor',
            ])->assertOk();

        $this->assertSame(
            'leitor',
            $operador->fresh()->perfil
        );

        $this->assertFalse(
            Gate::forUser($operador->fresh())
                ->allows('produtos.gravar')
        );
    }

    public function test_operador_nao_pode_alterar_perfis(): void
    {
        $operador = $this->usuario(
            'operador',
            'operador@gennus.test'
        );

        $leitor = $this->usuario(
            'leitor',
            'leitor@gennus.test'
        );

        $this->actingAs($operador)
            ->postJson('/php/usuario_perfil.php', [
                'usuario_id' => $leitor->id,
                'perfil' => 'operador',
            ])->assertForbidden();

        $this->assertSame(
            'leitor',
            $leitor->fresh()->perfil
        );
    }

    public function test_gestor_nao_pode_alterar_proprio_perfil(): void
    {
        $gestor = $this->usuario(
            'administrador',
            'gestor@gennus.test'
        );

        $this->actingAs($gestor)
            ->postJson('/php/usuario_perfil.php', [
                'usuario_id' => $gestor->id,
                'perfil' => 'leitor',
            ])->assertUnprocessable();

        $this->assertSame(
            'administrador',
            $gestor->fresh()->perfil
        );
    }

    public function test_nao_pode_promover_para_administrador(): void
    {
        $gestor = $this->usuario(
            'administrador',
            'gestor@gennus.test'
        );

        $operador = $this->usuario(
            'operador',
            'operador@gennus.test'
        );

        $this->actingAs($gestor)
            ->postJson('/php/usuario_perfil.php', [
                'usuario_id' => $operador->id,
                'perfil' => 'administrador',
            ])->assertUnprocessable();

        $this->assertSame(
            'operador',
            $operador->fresh()->perfil
        );
    }

    public function test_nao_pode_alterar_outro_administrador(): void
    {
        $gestor = $this->usuario(
            'administrador',
            'gestor@gennus.test'
        );

        $outroGestor = $this->usuario(
            'administrador',
            'outro@gennus.test'
        );

        $this->actingAs($gestor)
            ->postJson('/php/usuario_perfil.php', [
                'usuario_id' => $outroGestor->id,
                'perfil' => 'leitor',
            ])->assertUnprocessable();

        $this->assertSame(
            'administrador',
            $outroGestor->fresh()->perfil
        );
    }

    public function test_visitante_nao_pode_alterar_perfis(): void
    {
        $leitor = $this->usuario(
            'leitor',
            'leitor@gennus.test'
        );

        $this->postJson('/php/usuario_perfil.php', [
            'usuario_id' => $leitor->id,
            'perfil' => 'operador',
        ])->assertUnauthorized();
    }
}
