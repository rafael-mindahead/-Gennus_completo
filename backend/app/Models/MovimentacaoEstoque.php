<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
#[Fillable([
    'evento_id',
    'produto_id',
    'dispositivo_id',
    'origem',
    'quantidade_delta',
    'saldo_anterior',
    'saldo_posterior',
    'ocorrido_em'
])]
class MovimentacaoEstoque extends Model
{
    protected $table = 'movimentacoes_estoque';
    protected function casts(): array
    {
        return [
            'quantidade_delta' => 'decimal:3',
            'saldo_anterior' => 'decimal:3',
            'saldo_posterior' => 'decimal:3',
            'ocorrido_em' => 'datetime'
        ];
    }
}