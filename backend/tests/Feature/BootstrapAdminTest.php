<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BootstrapAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_primeiro_administrador(): void
    {
        $this->artisan('gennus:bootstrap-admin')
            ->expectsQuestion(
                'Nome do gestor',
                'Gestor Inicial'
            )
            ->expectsQuestion(
                'E-mail corporativo',
                'gestor@gennus.test'
            )
            ->expectsQuestion(
                'Senha do gestor',
                'SenhaForte123!ab'
            )
            ->expectsQuestion(
                'Confirme a senha',
                'SenhaForte123!ab'
            )
            ->expectsConfirmation(
                'Confirmar criação do primeiro gestor?',
                'yes'
            )
            ->assertExitCode(0);

        $usuario = User::where(
            'email',
            'gestor@gennus.test'
        )->firstOrFail();

        $this->assertSame(
            'administrador',
            $usuario->perfil
        );

        $this->assertTrue(
            Hash::check(
                'SenhaForte123!ab',
                $usuario->password
            )
        );
    }

    public function test_nao_cria_segundo_administrador(): void
    {
        $usuario = User::create([
            'name' => 'Gestor Existente',
            'email' => 'existente@gennus.test',
            'password' => 'SenhaForte123!ab',
        ]);

        $usuario->forceFill([
            'perfil' => 'administrador',
        ])->save();

        $this->artisan('gennus:bootstrap-admin')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 1);
    }
}
