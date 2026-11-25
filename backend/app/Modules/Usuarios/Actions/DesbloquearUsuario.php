<?php
namespace App\Modules\Usuarios\Actions;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
final class DesbloquearUsuario
{
    public function execute(User $gestor, int $id): void
    {
        Gate::forUser($gestor)->authorize('usuarios.gerenciar');
        DB::transaction(function () use ($gestor, $id){
            $usuario = User::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($usuario->perfil === 'administrador'){
                throw ValidationException::withMessages([
                    'usuario_id' => 'Operacao nao permitida para administradores.',
                ]);
            }
            if ($usuario->bloqueado_em === null){
                throw ValidationException::withMessages([
                    'usuario_id' => 'O usuario nao esta bloqueado.',
                ]);
            }
            $usuario->forceFill([
                'bloqueado_em' => null,
            ])->save();
            DB::table('usuario_auditorias')->insert([
                'gestor_id' => $gestor->id,
                'usuario_id' => $usuario->id,
                'gestor_nome' => $gestor->name,
                'usuario_nome' => $usuario->name,
                'acao' => 'desbloqueio',
                'ocorrido_em' => now(),
            ]);
        }, 3);
    }
}