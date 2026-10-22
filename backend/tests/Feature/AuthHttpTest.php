<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_csrf_disponivel(): void
    {
        $this->get('/php/csrf.php')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['csrf_token']
            ]);
    }

    public function test_cadastro_http(): void
    {
        $this->post('/php/usuario_novo.php', [
            'nome' => 'Ana',
            'sobrenome' => 'Silva',
            'email_corporativo' => 'ana@gennus.test',
            'telefone' => '41999999999',
            'cpf_cnpj' => '',
            'senha' => 'SenhaSegura123'
        ])->assertCreated()
          ->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('users', [
            'email' => 'ana@gennus.test'
        ]);
    }

    public function test_login_sessao_logout(): void
    {
        $usuario = User::create([
            'name' => 'Ana',
            'email' => 'ana@gennus.test',
            'password' => 'SenhaSegura123'
        ]);

        $this->get('/php/valida_sessao.php')
            ->assertUnauthorized();

        $this->post('/php/usuario_login.php', [
            'email' => 'ana@gennus.test',
            'senha' => 'SenhaSegura123'
        ])->assertOk()
          ->assertJsonPath('status', 'ok');

        $this->assertAuthenticatedAs($usuario);

        $this->get('/php/valida_sessao.php')
            ->assertOk();

        $this->post('/php/cliente_logoff.php')
            ->assertOk();

        $this->assertGuest();
    }

    public function test_login_invalido(): void
    {
        $this->post('/php/usuario_login.php', [
            'email' => 'inexistente@gennus.test',
            'senha' => 'SenhaErrada123'
        ])->assertUnauthorized()
          ->assertJsonPath(
              'mensagem',
              'Credenciais inválidas.'
          );

        $this->assertGuest();
    }
}
