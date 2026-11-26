<?php
namespace App\Http\Controllers;
use App\Modules\Usuarios\Actions\ListUsers;
use App\Modules\Usuarios\Actions\BloquearUsuario;
use App\Modules\Usuarios\Actions\DesbloquearUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Modules\Usuarios\Actions\AlterarSituacaoFuncional;
use Illuminate\Validation\Rule;
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
    public function bloquear(Request $request, BloquearUsuario $action): JsonResponse
    {
        $dados = $request->validate([
            'usuario_id' => [
                'required', 
                'integer',
                'min:1',
            ]
        ]);
        $action->execute($request->user(), (int)$dados['usuario_id']);
        return response()->json([
            'status' => 'ok',
            'mensagem' => 'Usuaerio desbloqueado com sucesso.',
            'data' => [],
        ]);
    }
    public function desbloquear(Request $request, DesbloquearUsuario $action): JsonResponse
    {
        $dados = $request->validate([
            'usuario_id' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);
        $action->execute($request->user(), (int)$dados['usuario_id']);
        return response()->json([
            'status' => 'ok',
            'mensagem' => 'Usuario desbloqueado com sucesso.',
            'data' => [],
        ]);
    }
    public function alterarSituacao(
        Request $request,
        AlterarSituacaoFuncional $action
    ): JsonResponse {
        $dados = $request->validate([
            'usuario_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'situacao_funcional' => [
                'required',
                Rule::in([
                    'ativo',
                    'inativo',
                    'desligado',
                ]),
            ],
        ]);

        $action->execute(
            $request->user(),
            (int) $dados['usuario_id'],
            $dados['situacao_funcional']
        );

        return response()->json([
            'status' => 'ok',
            'mensagem' => 'Situação funcional atualizada.',
            'data' => [],
        ]);
    }
}