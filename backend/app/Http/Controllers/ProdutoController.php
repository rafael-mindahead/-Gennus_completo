<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use App\Modules\Produtos\Actions\GetProduto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Modules\Produtos\Actions\CreateProduto;
use App\Modules\Produtos\Actions\UpdateProduto;
use App\Modules\Produtos\Actions\DeleteProduto;
use App\Modules\Produtos\Actions\RestoreProduto;
use DomainException;



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
    public function store(Request $request, CreateProduto $action): JsonResponse
    {
        try {
            $produto = $action->execute($request->all());
            return response()->json([
                'status' => 'ok',
                'mensagem' => 'Produto cadastrado com sucesso',
                'data' => $produto
            ], 201);
        } catch (ValidationException $e){
            return $this->erro(
                $e->validator->errors()->first(),
                422
            );
        }
    }
    public function update(Request $request, UpdateProduto $action): JsonResponse
    {
        $validator = Validator::make(['id' => $request->query('id')], [
            'id' => ['required', 'integer', 'min:1']
        ]);
        if($validator->fails()){
            return response()->json([
                'status' => 'nok',
                'mensagem' => 'ID do produto invalido',
                'data' => []
            ], 422);
        }
        try {
            $produto = $action->execute((int) $validator->validated()['id'], $request->all());
            return response()->json([
                'status' => 'ok',
                'mensagem' => 'Produto atualizado com sucesso',
                'data' => $produto
            ], 200);
        } catch (ModelNotFoundException $e){
            return response()->json([
                'status' => 'nok',
                'mensagem' => 'Produto nao encontrado',
                'data' => []
            ],404);
        } catch(ValidationException $e){
            return response()->json([
                'status' => 'nok',
                'mensagem' => $e->validator->errors()->first(),
                'data' => []
            ], 422);
        }
    }
    public function destroy(Request $request, DeleteProduto $action): JsonResponse
    {
        try {
            $id = $this->validarId($request);
            $action->execute($id, $request->user());
            return response()->json([
                'status' => 'ok',
                'mensagem' => 'Produto arquvido com sucesso',
                'data' => []
            ]);
        } catch (ValidationException $e){
            return $this->erro($e->validator->errors()->first(), 422);
        } catch (ModelNotFoundException $e){
            return $this->erro('Produto nao encontrado', 404);
        }
    }
    public function restore(Request $request, RestoreProduto $action): JsonResponse
    {
        try {
            $id = $this->validarId($request);
            $produto = $action->execute($id, $request->user());
            return response()->json([
                'status' => 'ok',
                'mensagem' => 'Produto restaurado com sucesso',
                'data' => $produto,
            ]);
        } catch (ValidationException $e){
            return $this->erro($e->errors()?collect($e->errors())->flatten()->first() : 'Dados invalidos.', 422);
        } catch (ModelNotFoundException $e){
            return $this->erro('produto nao encontrado.', 404);
        } catch (DomainException $e){
            return $this->erro($e->getMessage(), 410);
        }
    }
    private function validarId(Request $request): int
    {
        $validado = Validator::make(['id' => $request->query('id')], [
            'id' => ['required', 'integer', 'min:1']
        ])->validate();
        return (int) $validado['id'];
    }
    private function erro(string $mensagem, int $codigo): JsonResponse
    {
        return response()->json([
            'status' => 'nok',
            'mensagem' => $mensagem,
            'data' => []
        ], $codigo);
    }
}