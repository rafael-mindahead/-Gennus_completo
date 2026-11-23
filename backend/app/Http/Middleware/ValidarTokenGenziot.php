<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ValidarTokenGenziot
{
    public function handle(Request $request, Closure $next): Response
    {
        $esperado = config('services.genziot.token');
        $recebido = $request->header('X-Gennus-Service-Token');
        if (
            !is_string($esperado) ||
            strlen($esperado) < 32 ||
            !is_string($recebido) ||
            !hash_equals($esperado, $recebido)
        ){
            return response()->json([
                'status' => 'error',
                'mensagem' => 'Nao autorizado',
                'data' => []
            ],401);
        }
        return $next($request);
    }
}