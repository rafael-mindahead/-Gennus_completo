<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Models\MovimentacaoEstoque;
use App\Modules\Estoque\Actions\RegistrarMovimentacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RegistrarMovimentacaoTest extends TestCase
{
    use RefreshDatabase;

    private function criarProduto(): Produto
    {
        return Produto::create([
            'nome' => 'Produto IoT',
            'codigo_barras' => '7900000000001',
            'categoria' => 'Eletronicos',
            'unidade' => 'un',
            'custo' => '10.00',
            'preco' => '20.00',
            'estoque' => '10.000'
        ]);
    }

    private function evento(): array
    {
        return [
            'evento_id' =>
                '550e8400-e29b-41d4-a716-446655440000',
            'dispositivo_id' => 1,
            'codigo_barras' => '7900000000001',
            'quantidade_delta' => 5
        ];
    }

    public function test_registra_entrada(): void
    {
        $produto = $this->criarProduto();

        $registro = (new RegistrarMovimentacao())
            ->execute($this->evento());

        $this->assertSame('15.000', $produto->fresh()->estoque);
        $this->assertSame('10.000', $registro->saldo_anterior);
        $this->assertSame('15.000', $registro->saldo_posterior);

        $this->assertDatabaseCount(
            'movimentacoes_estoque',
            1
        );
    }

    public function test_nao_duplica_evento(): void
    {
        $produto = $this->criarProduto();
        $action = new RegistrarMovimentacao();

        $primeiro = $action->execute($this->evento());
        $segundo = $action->execute($this->evento());

        $this->assertEquals($primeiro->id, $segundo->id);
        $this->assertSame('15.000', $produto->fresh()->estoque);

        $this->assertDatabaseCount(
            'movimentacoes_estoque',
            1
        );
    }

    public function test_rejeita_saldo_insuficiente(): void
    {
        $produto = $this->criarProduto();
        $evento = $this->evento();
        $evento['quantidade_delta'] = -11;

        try {
            (new RegistrarMovimentacao())->execute($evento);
            $this->fail('Deveria rejeitar a movimentação.');
        } catch (ValidationException $e) {
            $this->assertSame(
                '10.000',
                $produto->fresh()->estoque
            );

            $this->assertDatabaseCount(
                'movimentacoes_estoque',
                0
            );
        }
    }

    public function test_rejeita_produto_desconhecido(): void
    {
        $this->expectException(
            ModelNotFoundException::class
        );

        (new RegistrarMovimentacao())
            ->execute($this->evento());
    }

    public function test_rejeita_uuid_reutilizado(): void
    {
        $this->criarProduto();
        $action = new RegistrarMovimentacao();

        $action->execute($this->evento());

        $evento = $this->evento();
        $evento['quantidade_delta'] = 7;

        $this->expectException(\DomainException::class);

        $action->execute($evento);
    }

    public function test_rejeita_uuid_entre_produtos_diferentes(): void
    {
        $primeiro = $this->criarProduto();

        $segundo = Produto::create([
            'nome' => 'Segundo produto',
            'codigo_barras' => '7891234567890',
            'categoria' => 'Eletronicos',
            'unidade' => 'un',
            'custo' => '15.00',
            'preco' => '30.00',
            'estoque' => '20.000'
        ]);

        $action = new RegistrarMovimentacao();

        $action->execute($this->evento());

        $eventoRepetido = $this->evento();
        $eventoRepetido['codigo_barras'] = '7891234567890';

        try {
            $action->execute($eventoRepetido);

            $this->fail('O UUID duplicado deveria ser rejeitado.');

        } catch (\DomainException $e) {
            $this->assertSame(
                '15.000',
                $primeiro->fresh()->estoque
            );

            $this->assertSame(
                '20.000',
                $segundo->fresh()->estoque
            );

            $this->assertDatabaseCount(
                'movimentacoes_estoque',
                1
            );
        }
    }
}