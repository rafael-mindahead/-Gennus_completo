<?php
namespace App\Modules\Estoque\Actions;
use App\Models\Produto;
use App\Models\MovimentacaoEstoque;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
final class RegistrarMovimentacao
{
    public function execute(array $dados): MovimentacaoEstoque
    {
        $evento = Validator::make($dados, [
            'evento_id' => [
                'required',
                'uuid'
            ],
            'dispositivo_id' => [
                'required',
                'integer',
                'min:1'
            ],
            'codigo_barras' => [
                'required',
                'string',
                'regex:/^(\d{8}|\d{12,14})$/'
            ],
            'quantidade_delta' => [
                'required',
                'integer',
                'between:-1000,1000',
                'not_in:0'
            ]
        ])->validate();
        return DB::transaction(function () use ($evento){
            $produto = Produto::where('codigo_barras', $evento['codigo_barras'])->lockForUpdate()->firstOrFail();

            if ($produto->unidade !== 'un'){
                throw ValidationException::withMessages([
                    'unidade' => 'Movimentacoes IoT exigem unidade un.'
                ]);
            }
            $existente = MovimentacaoEstoque::query()->where('evento_id', $evento['evento_id'])->first();
            if ($existente !== null){
                if (
                    (int) $existente->produto_id !== (int) $produto->id ||
                    (int) $existente->dispositivo_id !== (int) $evento['dispositivo_id'] ||
                    (int) $existente-> quantidade_delta !== (int) $evento['quantidade_delta'] ||
                    $existente->origem !== 'genziot'
                ){
                    throw new \DomainException('UUID reutilizado com dados diferentes.');
                }
                return $existente;
            }
            $saldoAnterior = $produto->estoque;
                if (!preg_match('/^\d{1,9}\.000$/', $saldoAnterior)) {
                    throw ValidationException::withMessages([
                        'estoque' =>
                            'O estoque em unidades deve ser inteiro.'
                ]);
            }
            $saldoNovo = (int) $saldoAnterior + (int) $evento['quantidade_delta'];
            if ($saldoNovo < 0 || $saldoNovo > 999999999){
                throw ValidationException::withMessages([
                    'estoque' => 'Movimentacao excede limites de estoque.'
                ]);
            }
            $saldoPosterior = $saldoNovo . '.000';
            $registro = MovimentacaoEstoque::Create([
                'evento_id' => $evento['evento_id'],
                'produto_id' => $produto->id,
                'dispositivo_id' => $evento['dispositivo_id'],
                'origem' => 'genziot',
                'quantidade_delta' => $evento['quantidade_delta'],
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior
            ]);
            $produto->estoque = $saldoPosterior;
            $produto->save();
            return $registro;
        }, 3);
    }
}