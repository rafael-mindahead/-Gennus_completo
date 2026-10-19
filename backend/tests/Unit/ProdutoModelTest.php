<?php
namespace Tests\Unit;

use App\Models\Produto;
use PHPUnit\Framework\TestCase;

class ProdutoModelTest extends TestCase
{
    public function test_model_usa_tabela_correta(): void
    {
        $produto = new Produto();

        $this->assertEquals(
            'produtos',
            $produto->getTable()
        );
    }

    public function test_model_permita_campos_do_produto(): void
    {
        $produto = new Produto();

        $this->assertContains(
            'nome',
            $produto->getFillable()
        );

        $this->assertContains(
            'estoque',
            $produto->getFillable()
        );
    }

    public function test_model_preserva_timestamps_legados(): void
    {
        $produto = new Produto();

        $this->assertEquals(
            'criado_em',
            $produto->getCreatedAtColumn()
        );

        $this->assertEquals(
            'atualizado_em',
            $produto->getUpdatedAtColumn()
        );
    }
}
