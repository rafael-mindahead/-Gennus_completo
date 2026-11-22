<?php

namespace App\Http\Controllers;

use App\Modules\Auth\Actions\RegisterUser;
use App\Modules\Auth\Actions\LoginUser;
use App\Modules\Auth\Actions\LogoutUser;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function csrf(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'mensagem' => '',
            'data' => [
                'csrf_token' => csrf_token()
            ]
        ])->header('Cache-Control', 'no-store');
    }
    
    public function register(Request $request, RegisterUser $action): JsonResponse 
    {
        try {
            $usuario = $action->execute(
                $request->all()
            );

            return response()->json([
                'status' => 'ok',
                'mensagem' => 'Usuário cadastrado com sucesso.',
                'data' => [
                    'id' => $usuario->id
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'nok',
                'mensagem' => $e->validator
                    ->errors()
                    ->first(),
                'data' => []
            ], 422);
        }
    }

    public function Login( Request $request, LoginUser $action): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email'
            ],
            'senha' => [
                'required',
                'string'
            ]
        ]);
        if ($validator->fails()){
            return response()->json([
                'status' => 'nok',
                'mensagem' => 'Credenciais inválidas.',
                'data' => []
            ], 401);
        }
        try {
            $usuario = $action->execute(
                $request->input('email'),
                $request->input('senha')
            );
        } catch (ValidationException $e){
            return response()->json([
                'status' => 'nok',
                'mensagem' => 'Credenciais inválidas.',
                'data' => []
            ], 401);
        }
        $request->session()->regenerate();
        return response()->json([
            'status' => 'ok',
            'mensagem' => 'Login realizado com sucesso',
            'data' => [
                'id' => $usuario->id,
                'nome'=> $usuario->name
            ]
        ]);
    }
    public function session(): JsonResponse
    {
        if (!Auth::check()){
            return response()->json([
                'status' => 'nok',
                'mensagem' => 'nao autenticado',
                'data' => []
            ], 401);
        }
        $usuario = Auth::user();
        return response()->json([
            'status' => 'ok',
            'mensagem' => '',
            'data' => [
                'id' => $usuario->id,
                'nome' => $usuario->name,
                'email_corporativo' => $usuario->email
            ]
        ]);
    }
    public function Logout( Request $request, LogoutUser $action): JsonResponse
    {
        $action->execute();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json([
            'status' => 'ok',
            'mensagem' => 'sessão encerrado com sucesso',
            'data' => []
        ]);
    }
}
