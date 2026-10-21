<?php

namespace Tests\Feature;

use App\Modules\Auth\Actions\RegisterUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RegisterUserTest extends TestCase
{
    use RefreshDatabase;

    private function dadosValidos(): array
    {
        return [
            'nome' => 'Ana',
            'sobrenome' => 'Silva',
            'email_corporativo' => 'ana@gennus.test',
            'telefone' => '41999999999',
            'cpf_cnpj' => '',
            'senha' => 'SenhaSegura123'
        ];
    }

    public function test_cadastra_usuario(): void
    {
        $usuario = (new RegisterUser())
            ->execute($this->dadosValidos());

        $this->assertNotNull($usuario->id);

        $this->assertDatabaseHas('users', [
            'email' => 'ana@gennus.test',
            'name' => 'Ana',
            'sobrenome' => 'Silva'
        ]);

        $this->assertTrue(
            Hash::check(
                'SenhaSegura123',
                $usuario->password
            )
        );
    }

    public function test_impede_email_duplicado(): void
    {
        $action = new RegisterUser();

        $action->execute($this->dadosValidos());

        $this->expectException(
            ValidationException::class
        );

        $action->execute($this->dadosValidos());
    }

    public function test_rejeita_senha_curta(): void
    {
        $dados = $this->dadosValidos();
        $dados['senha'] = '123';

        $this->expectException(
            ValidationException::class
        );

        (new RegisterUser())->execute($dados);
    }
}
