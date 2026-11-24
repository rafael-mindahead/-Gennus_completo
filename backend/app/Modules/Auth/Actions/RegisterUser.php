<?php
namespace App\Modules\Auth\Actions;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class RegisterUser
{
    public function execute(array $data, User $gestor): User
    {
        Gate::forUser($gestor)
            ->authorize('usuarios.criar');

        $validado = Validator::make($data, [
            'nome' => ['required', 'string', 'max:120'],
            'sobrenome' => ['required', 'string', 'max:120'],

            'email_corporativo' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],

            'telefone' => ['nullable', 'string', 'max:18'],
            'cpf_cnpj' => ['nullable', 'string', 'max:18'],

            'senha' => [
                'required',
                'string',
                'min:8',
                'max:120',
            ],

            'perfil' => [
                'required',
                Rule::in(['operador', 'leitor']),
            ],
        ])->validate();

        return DB::transaction(function () use ($validado) {
            $usuario = User::create([
                'name' => $validado['nome'],
                'sobrenome' => $validado['sobrenome'],
                'email' => $validado['email_corporativo'],
                'telefone' => $validado['telefone'] ?? null,
                'cpf_cnpj' => $validado['cpf_cnpj'] ?? null,
                'password' => $validado['senha'],
            ]);

            $usuario->forceFill([
                'perfil' => $validado['perfil'],
            ])->save();

            return $usuario->refresh();
        });
    }
}
