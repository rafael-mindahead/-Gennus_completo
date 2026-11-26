<?php

namespace App\Modules\Usuarios\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AlterarPerfilUsuario
{
    public function execute(
        User $gestor,
        int $id,
        string $novoPerfil
    ): void {
        Gate::forUser($gestor)
            ->authorize('usuarios.gerenciar');

        if (!in_array(
            $novoPerfil,
            ['operador', 'leitor'],
            true
        )) {
            throw ValidationException::withMessages([
                'perfil' => 'Perfil inválido.',
            ]);
        }

        DB::transaction(function () use (
            $gestor,
            $id,
            $novoPerfil
        ) {
            $usuario = User::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $usuario->id === $gestor->id ||
                $usuario->perfil === 'administrador'
            ) {
                throw ValidationException::withMessages([
                    'usuario_id' =>
                        'Não é permitido alterar o perfil de administradores.',
                ]);
            }

            $perfilAnterior = $usuario->perfil;

            if ($perfilAnterior === $novoPerfil) {
                throw ValidationException::withMessages([
                    'perfil' =>
                        'O funcionário já possui esse perfil.',
                ]);
            }

            $agora = now();

            $usuario->forceFill([
                'perfil' => $novoPerfil,
            ])->save();

            DB::table('usuario_auditorias')->insert([
                'gestor_id' => $gestor->id,
                'usuario_id' => $usuario->id,
                'gestor_nome' => $gestor->name,
                'usuario_nome' => $usuario->name,
                'acao' => 'alteracao_perfil',
                'perfil_anterior' => $perfilAnterior,
                'perfil_novo' => $novoPerfil,
                'ocorrido_em' => $agora,
            ]);

            // Invalida sessões persistidas após
            // uma alteração de permissões.
            if (config('session.driver') === 'database') {
                DB::table('sessions')
                    ->where('user_id', $usuario->id)
                    ->delete();
            }
        }, 3);
    }
}
