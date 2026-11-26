<?php

namespace App\Modules\Usuarios\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AlterarSituacaoFuncional
{
    public function execute(
        User $gestor,
        int $id,
        string $novaSituacao
    ): void {
        Gate::forUser($gestor)
            ->authorize('usuarios.gerenciar');

        if (!in_array(
            $novaSituacao,
            ['ativo', 'inativo', 'desligado'],
            true
        )) {
            throw ValidationException::withMessages([
                'situacao_funcional' => 'Situação inválida.',
            ]);
        }

        DB::transaction(function () use (
            $gestor,
            $id,
            $novaSituacao
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
                        'Não é permitido alterar a situação de administradores.',
                ]);
            }

            $situacaoAnterior = $usuario->situacao_funcional;

            if ($situacaoAnterior === $novaSituacao) {
                throw ValidationException::withMessages([
                    'situacao_funcional' =>
                        'O funcionário já possui essa situação.',
                ]);
            }

            $agora = now();

            $usuario->forceFill([
                'situacao_funcional' => $novaSituacao,
            ])->save();

            DB::table('usuario_auditorias')->insert([
                'gestor_id' => $gestor->id,
                'usuario_id' => $usuario->id,
                'gestor_nome' => $gestor->name,
                'usuario_nome' => $usuario->name,
                'acao' => 'situacao_funcional',
                'situacao_anterior' => $situacaoAnterior,
                'situacao_nova' => $novaSituacao,
                'ocorrido_em' => $agora,
            ]);

            if (
                $novaSituacao === 'desligado' &&
                config('session.driver') === 'database'
            ) {
                DB::table('sessions')
                    ->where('user_id', $usuario->id)
                    ->delete();
            }
        }, 3);
    }
}
