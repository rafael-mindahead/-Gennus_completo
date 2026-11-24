<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_pode_cadastrar_usuario(): void
    {
        $this->postJson('/php/usuario_novo.php', [
            'nome' => 'Ana',
            'sobrenome' => 'Silva',
            'email_corporativo' => 'ana@gennus.test',
            'senha' => 'SenhaSegura123',
        ])->assertUnauthorized();

        $this->assertDatabaseMissing('users', [
            'email' => 'ana@gennus.test',
        ]);
    }
}
