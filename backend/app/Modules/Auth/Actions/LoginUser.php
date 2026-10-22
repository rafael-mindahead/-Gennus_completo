<?php

namespace App\Modules\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class LoginUser
{
    public function execute(string $email, string $senha): User
    {
        $autenticado = Auth::attempt([
            'email' => $email,
            'password' => $senha
        ]);

        if (!$autenticado){
            throw ValidationException::withMessages([
                'email' => 'Credenciais inválidas.',
                'senha' => 'Credenciais inválidas.'
            ]);
        }
        return Auth::user();
    }
}
