<?php
namespace Tests\Feature;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstoqueInternoHttpTest extends TestCase
{
    use RefreshDatabase;

    private const URL =
        '/api/internal/v1/estoque/movimentacoes';

    private const TOKEN =
        '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'services.genziot.token',
            self::TOKEN
        );

        Produto::create([
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

    private function headers(): array
    {
        return [
            'X-Gennus-Service-Token' => self::TOKEN
        ];
    }

    public function test_registra_evento_http(): void
    {
        $this->postJson(
            self::URL,
            $this->evento(),
            $this->headers()
        )
            ->assertCreated()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath(
                'data.saldo_posterior',
                '15.000'
            );
    }

    public function test_reenvio_retorna_200(): void
    {
        $this->postJson(
            self::URL,
            $this->evento(),
            $this->headers()
        )->assertCreated();

        $this->postJson(
            self::URL,
            $this->evento(),
            $this->headers()
        )->assertOk();

        $this->assertDatabaseCount(
            'movimentacoes_estoque',
            1
        );
    }

    public function test_rejeita_sem_token(): void
    {
        $this->postJson(
            self::URL,
            $this->evento()
        )->assertUnauthorized();
    }

    public function test_rejeita_token_invalido(): void
    {
        $this->postJson(
            self::URL,
            $this->evento(),
            ['X-Gennus-Service-Token' => 'invalido']
        )->assertUnauthorized();
    }

    public function test_rejeita_sem_configuracao(): void
    {
        config()->set(
            'services.genziot.token',
            null
        );

        $this->postJson(
            self::URL,
            $this->evento(),
            $this->headers()
        )->assertUnauthorized();
    }

    public function test_rejeita_uuid_conflitante(): void
    {
        $this->postJson(
            self::URL,
            $this->evento(),
            $this->headers()
        )->assertCreated();

        $evento = $this->evento();
        $evento['quantidade_delta'] = 7;

        $this->postJson(
            self::URL,
            $evento,
            $this->headers()
        )->assertStatus(409);
    }

    public function test_rejeita_estoque_insuficiente(): void
    {
        $evento = $this->evento();
        $evento['quantidade_delta'] = -11;

        // Confirma que o produto do evento existe.
        $this->assertDatabaseHas('produtos', [
            'codigo_barras' => $evento['codigo_barras'],
            'estoque' => '10.000'
        ]);

        $this->postJson(
            self::URL,
            $evento,
            $this->headers()
        )->assertUnprocessable();

        // A tentativa não pode gerar movimentações.
        $this->assertDatabaseCount(
            'movimentacoes_estoque',
            0
        );
    }


    public function test_rejeita_produto_inexistente(): void
    {
        $evento = $this->evento();
        $evento['codigo_barras'] = '7891234567890';

        $this->postJson(
            self::URL,
            $evento,
            $this->headers()
        )->assertNotFound();
    }
}
