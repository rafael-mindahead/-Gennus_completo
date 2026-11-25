<?php
namespace App\Modules\Usuarios\Actions;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class BloquearUsuario
{
    public function execute(User $gestor, int $id): void
    {
        Gate::forUser($gestor)->authorize('usuarios.gerenciar');
        DB::transaction(function () use ($gestor, $id){
            $usuario = User::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($usuario->id === $gestor->id || $usuario->perfil === 'administrador'){
                throw ValidationException::withMessages([
                    'usuario_id' => 'Nao e permitido bloquear administradores',
                ]);  
            }
            if ($usuario->bloqueado_em !== null){
                throw ValidationException::withMessages([
                    'usuario_id' => 'O usuario ja esta bloqueado.',
                ]);
            }
            $agora = now();
            $usuario->forceFill([
                'bloqueado_em' => $agora,
            ])->save(); 
            DB::table('usuario_auditorias')->insert([
                'gestor_id' => $gestor->id,
                'usuario_id' => $usuario->id,
                'gestor_nome' => $gestor->name,
                'usuario_nome' => $usuario->name,
                'acao' => 'bloqueio',
                'ocorrido_em' => $agora,
            ]);
            if (config('session.driver')=== 'database'){
                DB::table('sessions')->where('user_id', $usuario->id)->delete();
            }
        },3);
    }
}