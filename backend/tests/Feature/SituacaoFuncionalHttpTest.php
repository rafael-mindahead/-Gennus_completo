<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SituacaoFuncionalHttpTest extends TestCase
{
    use RefreshDatabase;

    private function funcionario(string $situacao): User
    {
        $usuario = User::create([
            'name' => 'Funcionario Teste',
            'email' => 'funcionario@gennus.test',
            'password' => 'SenhaSegura123',
        ]);

        $usuario->forceFill([
            'perfil' => 'operador',
            'situacao_funcional' => $situacao,
        ])->save();

        return $usuario->fresh();
    }

    public function test_inativo_pode_acessar_sistema(): void
    {
        $this->funcionario('inativo');

        $this->postJson('/php/usuario_login.php', [
            'email' => 'funcionario@gennus.test',
            'senha' => 'SenhaSegura123',
        ])->assertOk();

        $this->getJson('/php/valida_sessao.php')
            ->assertOk();
    }

    public function test_desligado_nao_pode_fazer_login(): void
    {
        $this->funcionario('desligado');

        $this->postJson('/php/usuario_login.php', [
            'email' => 'funcionario@gennus.test',
            'senha' => 'SenhaSegura123',
        ])->assertUnauthorized();

        $this->assertGuest();
    }

    public function test_desligamento_interrompe_sessao_ativa(): void
    {
        $usuario = $this->funcionario('ativo');

        $this->actingAs($usuario);

        $this->getJson('/php/valida_sessao.php')
            ->assertOk();

        // Simula o gestor alterando a situação
        // do funcionário no banco.
        $usuario->forceFill([
            'situacao_funcional' => 'desligado',
        ])->save();

        $this->getJson('/php/valida_sessao.php')
            ->assertUnauthorized();

        $this->getJson('/php/produto_get.php')
            ->assertUnauthorized();
    }
}
