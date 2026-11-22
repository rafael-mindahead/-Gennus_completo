<?php
namespace App\Modules\Produtos\Actions;
use App\Models\Produto;

final class DeleteProduto
{
    public function execute(int $id): void
    {
        $produto = Produto::query()->findOrFail($id);
        $produto->delete();
    }
}