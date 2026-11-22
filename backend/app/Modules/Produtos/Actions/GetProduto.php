<?php

namespace App\Modules\Produtos\Actions;

use App\Models\Produto;
use Illuminate\Database\Eloquent\Collection;

final class GetProduto
{
    public function execute(?int $id = null): Collection
    {
        if ($id !== null ){
            $produto = Produto::query()->findOrFail($id);
            return new Collection([$produto]);
        }
        return Produto::query()->orderBy('id')->get();
    }
}
