<?php
namespace App\Modules\Usuarios\Actions;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

final class ListUsers
{
    public function execute(User $gestor, int $porPagina = 20): LengthAwarePaginator{
        Gate::forUser($gestor)->authorize('usuarios.gerenciar');
        return User::query()->select([
            'id',
            'name',
            'sobrenome',
            'email',
            'perfil',
            'created_at',
            'situacao_funcional',
            'bloqueado_em',
        ])->orderBy('id')->paginate($porPagina)->through(
            fn(User $usuario) => [
                'id' => $usuario->id,
                'nome' => $usuario->name,
                'sobrenome' => $usuario->sobrenome,
                'email_corporativo' => $usuario->email,
                'perfil' => $usuario->perfil,
                'criado_em' => $usuario->created_at?->toIso8601String(),
                'situacao_funcional' => $usuario->situacao_funcional,
                'bloqueado' => $usuario->bloqueado_em !== null,
            ]
        );
    }
}