<?php
namespace App\Http\Middleware;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class GarantirContaDesbloqueada
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()){
            $usuario = User::query()->find(Auth::id());

            if ($usuario === null || $usuario->bloqueado_em !== null || !in_array($usuario->situacao_funcional, ['ativo', 'inativo'], true)){
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Conta bloqueada ou indisponivel.',
                    'data' => [],
                ], 401);
            }
        }
        return $next($request);
    }
}