<?php

namespace App\Modules\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class RegisterUser
{
    public function execute(array $data): User
    {
        $dados['cpf_cnpj'] = $dados['cpf_cnpj'] ?? null;

        $validado = Validator::make($data,[
            'nome' => [
                'required',
                'string',
                'max:120'
            ],
            'sobrenome' => [
                'required',
                'string',
                'max:120'
            ],
            'email_corporativo' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'email')
            ],
            'telefone' => [
                'nullable',
                'string',
                'max:18',
                Rule::unique('users', 'telefone')
            ],
            'cpf_cnpj' => [
                'nullable',
                'string',
                'max:18',
                Rule::unique('users', 'cpf_cnpj')
            ],
            'senha' => [
                'required',
                'string',
                'min:8',
                'max:120'
            ]
        ])->validate();
        return User::create([
            'name' => $validado['nome'],
            'sobrenome' => $validado['sobrenome'],
            'email' => $validado['email_corporativo'],
            'telefone' => $validado['telefone'] ?? null,
            'cpf_cnpj' => $validado['cpf_cnpj'] ?? null,
            'password' => $validado['senha']
        ]);
    }
}