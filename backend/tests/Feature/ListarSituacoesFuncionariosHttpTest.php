<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListarSituacoesFuncionariosHttpTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(
        string $perfil,
        string $email
    ): User {
        $usuario = User::create([
            'name' => 'Usuario Teste',
            'email' => $email,
            'password' => 'SenhaSegura123',
        ]);

        $usuario->forceFill([
            'perfil' => $perfil,
        ])->save();

        return $usuario->fresh();
    }

    public function test_listagem_exibe_situacao_e_bloqueio(): void
    {
        $gestor = $this->usuario(
            'administrador',
            'gestor@gennus.test'
        );

        $funcionario = $this->usuario(
            'operador',
            'operador@gennus.test'
        );

        $funcionario->forceFill([
            'situacao_funcional' => 'inativo',
            'bloqueado_em' => now(),
        ])->save();

        $resposta = $this->actingAs($gestor)
            ->getJson('/php/usuarios_get.php');

        $resposta->assertOk();

        $dados = collect(
            $resposta->json('data.usuarios')
        )->firstWhere('id', $funcionario->id);

        $this->assertNotNull($dados);

        $this->assertSame(
            'inativo',
            $dados['situacao_funcional'] ?? null
        );

        $this->assertTrue(
            $dados['bloqueado'] ?? false
        );

        $this->assertArrayNotHasKey(
            'password',
            $dados
        );

        $this->assertArrayNotHasKey(
            'bloqueado_em',
            $dados
        );
    }

    public function test_gestor_pode_reativar_funcionario(): void
    {
        $gestor = $this->usuario(
            'administrador',
            'gestor@gennus.test'
        );

        $funcionario = $this->usuario(
            'operador',
            'operador@gennus.test'
        );

        $this->actingAs($gestor);

        $this->postJson('/php/usuario_situacao.php', [
            'usuario_id' => $funcionario->id,
            'situacao_funcional' => 'desligado',
        ])->assertOk();

        $this->postJson('/php/usuario_situacao.php', [
            'usuario_id' => $funcionario->id,
            'situacao_funcional' => 'ativo',
        ])->assertOk();

        $this->assertSame(
            'ativo',
            $funcionario->fresh()->situacao_funcional
        );

        $this->assertDatabaseHas(
            'usuario_auditorias',
            [
                'usuario_id' => $funcionario->id,
                'gestor_id' => $gestor->id,
                'acao' => 'situacao_funcional',
                'situacao_anterior' => 'desligado',
                'situacao_nova' => 'ativo',
            ]
        );
    }
}
