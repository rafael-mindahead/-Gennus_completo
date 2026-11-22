<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EstoqueSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_produtos_possuem_codigo_barras(): void
    {
        $this->assertTrue(
            Schema::hasColumn('produtos', 'codigo_barras')
        );
    }

    public function test_tabela_movimentacoes_existe(): void
    {
        $this->assertTrue(
            Schema::hasColumns('movimentacoes_estoque', [
                'id',
                'evento_id',
                'produto_id',
                'dispositivo_id',
                'origem',
                'quantidade_delta',
                'saldo_anterior',
                'saldo_posterior',
                'ocorrido_em',
                'created_at',
                'updated_at'
            ])
        );
    }
}
