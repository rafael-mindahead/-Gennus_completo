<?php
namespace App\Modules\Produtos\Actions;
use App\Models\Produto;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class UpdateProduto
{
    public function execute(int $id, array $dados): Produto
    {
        $produto = Produto::query()->findOrFail($id);

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
            ]
        ])->validate();

        $produto->fill($validado);
        $produto->save();

        return $produto->refresh();
    }
}