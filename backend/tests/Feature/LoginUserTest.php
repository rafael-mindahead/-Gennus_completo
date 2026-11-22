<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Auth\Actions\LoginUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LoginUserTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuario(): User
    {
        return User::create([
            'name' => 'Ana',
            'sobrenome' => 'Silva',
            'email' => 'ana@gennus.test',
            'password' => 'SenhaSegura123'
        ]);
    }

    public function test_login_valido(): void
    {
        $usuario = $this->criarUsuario();

        $resultado = (new LoginUser())->execute(
            'ana@gennus.test',
            'SenhaSegura123'
        );

        $this->assertTrue(Auth::check());

        $this->assertEquals(
            $usuario->id,
            $resultado->id
        );
    }

    public function test_senha_incorreta(): void
    {
        $this->criarUsuario();

        $this->expectException(
            ValidationException::class
        );

        (new LoginUser())->execute(
            'ana@gennus.test',
            'SenhaErrada'
        );
    }

    public function test_email_inexistente(): void
    {
        $this->expectException(
            ValidationException::class
        );

        (new LoginUser())->execute(
            'naoexiste@gennus.test',
            'SenhaSegura123'
        );
    }
}
