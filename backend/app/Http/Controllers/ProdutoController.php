<?php
namespace App\Http\Controllers;
use App\Modules\Produtos\Actions\GetProduto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class ProdutoController extends Controller
{
    public function index(Request $request, GetProduto $action): JsonResponse
    {
        $id = $request->query('id');
        if ($id !== null){
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'integer', 'min:1']
            ]);
            if ($validator->fails()){
                return response()->json([
                    'status' => 'nok',
                    'mensagem' => 'ID do produto invalido',
                    'data' => []   
                ], 422);
            }
        }
        try {
            $produtos = $action->execute($id === null ? null : (int) $id);
        } catch (ModelNotFoundException $e){
            return response()->json([
                'status' => 'nok',
                'mensagem' => 'Produto nao encontrado',
                'data' => []
            ], 404);
        }
        return response()->json([
            'status' => 'ok',
            'mensagem' => 'Consulta realizada com sucesso',
            'data' => $produtos
        ]);
    }
}