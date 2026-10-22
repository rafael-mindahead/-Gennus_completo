<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'nome',
    'categoria',
    'unidade',
    'custo',
    'preco',
    'estoque'
])]

class Produto extends Model
{
    protected $table = 'produtos';

    protected function casts(): array
    {
        return [
            'custo' => 'decimal:2',
            'preco' => 'decimal:2',
            'estoque' => 'decimal:3'
        ];
    }
}
