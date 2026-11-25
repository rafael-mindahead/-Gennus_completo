<?php
namespace App\Http\Controllers;
use App\Modules\Usuarios\Actions\ListUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UsuarioController extends Controller
{
    public function index(Request $request, ListUsers $action): JsonResponse
    {
        $validado = $request->validate([
            'per_page' => [
                'sometimes',
                'integer',
                'between:1,50',
            ],
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ]);
        $usuarios = $action->execute($request->user(), (int) ($validado['per_page']?? 20));
        return response()->json([
            'status' => 'ok',
            'mensagem' => 'Consulta realizada com sucesso', 
            'data' => [
                'usuarios' => $usuarios->items(),
                'paginacao' => [
                    'pagina_atual' => $usuarios->currentPage(),
                    'por_pagina' => $usuarios->perPage(),
                    'total' => $usuarios->total(),
                    'ultima_pagina' => $usuarios->lastPage(),
                ],
            ],
        ]);
    } 
}