<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $table = 'produtos';

    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'nome',
        'categoria',
        'unidade',
        'custo',
        'preco',
        'estoque',
    ];

    protected function casts(): array
    {
        return [
            'custo' => 'decimal:2',
            'preco' => 'decimal:2',
            'estoque' => 'decimal:3',
        ];
    }
}
