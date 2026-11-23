<?php
namespace App\Http\Controllers;
use App\Modules\Estoque\Actions\RegistrarMovimentacao;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use DomainException;

final class EstoqueInternoController extends Controller
{
    public function store(Request $request, RegistrarMovimentacao $action): JsonResponse
    {
        try{
            $registro = $action->execute($request->all());
            $novo = $registro->wasRecentlyCreated;
            return response()->json([
                'status' => 'ok',
                'mensagem' => $novo ? 'Movimentacao criada com sucesso.' : 'evento ja processado',
                'data' => [
                    'evento_id' => $registro->evento_id,
                    'produto_id' => $registro->produto_id,
                    'saldo_posterior' => $registro->saldo_posterior,
                ]
            ], $novo ? 201 : 200);
        } catch (ValidationException $e){
            return $this->erro(['produto' => ['produto nao encontrado']], 404);
        } catch (ModelNotFoundException $e){
            return $this->erro(['produto' => ['produto nao encontrado']], 404);
        } catch (DomainException $e){
            return $this->erro(['evento_id' => [$e->getMessage()]], 409);
        }
    }
    private function erro(array $erros, int $codigo): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'mensagem' => collect($erros)->flatten()->first(),
            'data' => [],
            'errors' => $erros
        ], $codigo);
    }
}