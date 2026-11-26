<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GerenciarSituacaoFuncionalHttpTest extends TestCase
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

    public function test_gestor_marca_funcionario_inativo(): void
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

        $this->postJson('/php/usuario_situacao.php', [
            'usuario_id' => $operador->id,
            'situacao_funcional' => 'inativo',
        ])->assertOk();

        $this->assertSame(
            'inativo',
            $operador->fresh()->situacao_funcional
        );

        $this->assertDatabaseHas('usuario_auditorias', [
            'gestor_id' => $gestor->id,
            'usuario_id' => $operador->id,
            'acao' => 'situacao_funcional',
            'situacao_anterior' => 'ativo',
            'situacao_nova' => 'inativo',
        ]);
    }

    public function test_gestor_desliga_funcionario(): void
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

        $this->postJson('/php/usuario_situacao.php', [
            'usuario_id' => $operador->id,
            'situacao_funcional' => 'desligado',
        ])->assertOk();

        $this->assertSame(
            'desligado',
            $operador->fresh()->situacao_funcional
        );

        $this->actingAs($operador);

        $this->getJson('/php/valida_sessao.php')
            ->assertUnauthorized();
    }

    public function test_operador_nao_altera_situacao(): void
    {
        $operador = $this->usuario(
            'operador',
            'operador@gennus.test'
        );

        $leitor = $this->usuario(
            'leitor',
            'leitor@gennus.test'
        );

        $this->actingAs($operador);

        $this->postJson('/php/usuario_situacao.php', [
            'usuario_id' => $leitor->id,
            'situacao_funcional' => 'desligado',
        ])->assertForbidden();

        $this->assertSame(
            'ativo',
            $leitor->fresh()->situacao_funcional
        );
    }

    public function test_gestor_nao_altera_propria_situacao(): void
    {
        $gestor = $this->usuario(
            'administrador',
            'gestor@gennus.test'
        );

        $this->actingAs($gestor);

        $this->postJson('/php/usuario_situacao.php', [
            'usuario_id' => $gestor->id,
            'situacao_funcional' => 'desligado',
        ])->assertUnprocessable();

        $this->assertSame(
            'ativo',
            $gestor->fresh()->situacao_funcional
        );
    }

    public function test_rejeita_situacao_invalida(): void
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

        $this->postJson('/php/usuario_situacao.php', [
            'usuario_id' => $operador->id,
            'situacao_funcional' => 'ferias',
        ])->assertUnprocessable();

        $this->assertSame(
            'ativo',
            $operador->fresh()->situacao_funcional
        );
    }

    public function test_visitante_nao_altera_situacao(): void
    {
        $operador = $this->usuario(
            'operador',
            'operador@gennus.test'
        );

        $this->postJson('/php/usuario_situacao.php', [
            'usuario_id' => $operador->id,
            'situacao_funcional' => 'inativo',
        ])->assertUnauthorized();
    }
}
