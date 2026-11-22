<?php
namespace App\Modules\Produtos\Actions;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class UpdateProduto
{
    public function execute(int $id, array $dados): Produto
    {
        return DB::transaction(function () use ($id, $dados){
            $produto = Produto::query()->lockForUpdate()->findOrFail($id);
            $validado = Validator::make($dados, [
                'nome' => [
                    'required',
                    'string',
                    'max:150'
                ],
                'categoria' => [
                    'nullable',
                    'string',
                    'max:120'
                ],
                'unidade' => [
                    'required',
                    Rule::in(['un', 'kg', 'L'])
                ],
                'custo' => [
                    'required',
                    'numeric',
                    'min:0',
                    'regex:/^\d{1,10}(\.\d{1,2})?$/'
                ],
                'preco' => [
                    'required',
                    'numeric',
                    'min:0',
                    'regex:/^\d{1,10}(\.\d{1,2})?$/'
                ],
                'estoque' => [
                    'required',
                    'numeric',
                    'min:0',
                    'regex:/^\d{1,9}(\.\d{1,3})?$/'
                ],
                'codigo_barras' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'regex:/^(\d{8}|\d{12,14})$/',
                    Rule::unique('produtos', 'codigo_barras')
                        ->ignore($produto->id)
                ]
            ])->validate();
            $partes = explode('.', (string) $validado['estoque'], 2);
            $estoqueInformado = (string) (int) $partes[0] . '.'. str_pad($partes[1] ?? '', 3, '0');
            if ($estoqueInformado !== $produto->estoque){
                throw ValidationException::withMessages([
                    'estoque' =>
                        'Estoque alterado. Atualize a página. ' .
                        'Movimentações devem ser registradas ' .
                        'pelo módulo de estoque.'
                ]);
            }
            unset($validado['estoque']);
            $produto->fill($validado);
            $produto->save();
            return $produto->refresh();
        }, 3);
    }
}