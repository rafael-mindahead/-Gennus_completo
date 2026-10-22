<?php

namespace App\Modules\Produtos\Actions;

use Illuminate\Validation\Rule;
use App\Models\Produto;
use Illuminate\Support\Facades\Validator;

final class CreateProduto
{
    public function execute(array $dados): Produto
    {
        $validado = Validator::make($dados, [
            'nome' => [
                'required',
                'string',
                'max:120'
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
        return Produto::create($validado);
    }
}
