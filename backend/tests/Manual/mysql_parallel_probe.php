
<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';

$app->make(
    Illuminate\Contracts\Console\Kernel::class
)->bootstrap();

use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use App\Modules\Estoque\Actions\RegistrarMovimentacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

// Segurança: nunca executar no banco principal.
if (config('database.default') !== 'mysql') {
    throw new RuntimeException(
        'Conexao precisa ser MySQL.'
    );
}

$nomeBanco = DB::selectOne(
    'SELECT DATABASE() AS nome'
)->nome;

if ($nomeBanco !== 'gennus_v2_test') {
    throw new RuntimeException(
        "Banco proibido para testes: {$nomeBanco}"
    );
}

// Cada worker executa em seu próprio processo PHP.
if (($argv[1] ?? '') === '--worker') {
    try {
        $json = base64_decode($argv[2] ?? '', true);

        if ($json === false) {
            throw new RuntimeException('Evento invalido.');
        }

        $evento = json_decode(
            $json,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $inicio = (float) ($argv[3] ?? 0);

        // Sincroniza o início dos workers.
        while (microtime(true) < $inicio) {
            usleep(10000);
        }

        $registro = (new RegistrarMovimentacao())
            ->execute($evento);

        echo "OK evento={$registro->evento_id}\n";
        exit(0);

    } catch (Throwable $e) {
        fwrite(
            STDERR,
            get_class($e) . ': ' .
            $e->getMessage() . PHP_EOL
        );
        exit(1);
    }
}

function criarProdutoTeste(): Produto
{
    return Produto::create([
        'nome' => 'Concorrencia MySQL',
        'codigo_barras' =>
            '789' . random_int(1000000000, 9999999999),
        'categoria' => 'Teste',
        'unidade' => 'un',
        'custo' => '5.00',
        'preco' => '10.00',
        'estoque' => '10.000'
    ]);
}

function verificarConcorrencia(
    string $cenario,
    Produto $produto,
    array $eventos,
    string $saldoEsperado,
    int $registrosEsperados
): void {
    $inicio = microtime(true) + 3;
    $processos = [];

    foreach ($eventos as $evento) {
        $processo = new Process([
            PHP_BINARY,
            __FILE__,
            '--worker',
            base64_encode(
                json_encode($evento, JSON_THROW_ON_ERROR)
            ),
            (string) $inicio
        ]);

        $processo->setTimeout(30);
        $processo->start();

        $processos[] = $processo;
    }

    foreach ($processos as $processo) {
        $processo->wait();

        if (!$processo->isSuccessful()) {
            throw new RuntimeException(
                $processo->getErrorOutput()
            );
        }
    }

    $saldo = $produto->fresh()->estoque;

    $total = MovimentacaoEstoque::where(
        'produto_id',
        $produto->id
    )->count();

    if (
        $saldo !== $saldoEsperado ||
        $total !== $registrosEsperados
    ) {
        throw new RuntimeException(
            "{$cenario} falhou: " .
            "saldo={$saldo}, movimentacoes={$total}"
        );
    }

    echo "PASSOU {$cenario}: " .
        "saldo={$saldo}, movimentacoes={$total}\n";
}

// Cenário A: 8 UUIDs diferentes.
$produtoA = criarProdutoTeste();
$eventosA = [];

for ($i = 0; $i < 8; $i++) {
    $eventosA[] = [
        'evento_id' => (string) Str::uuid(),
        'dispositivo_id' => 1,
        'codigo_barras' => $produtoA->codigo_barras,
        'quantidade_delta' => 1
    ];
}

verificarConcorrencia(
    '8 eventos diferentes',
    $produtoA,
    $eventosA,
    '18.000',
    8
);

// Cenário B: 8 entregas do mesmo UUID.
$produtoB = criarProdutoTeste();

$eventoB = [
    'evento_id' => (string) Str::uuid(),
    'dispositivo_id' => 1,
    'codigo_barras' => $produtoB->codigo_barras,
    'quantidade_delta' => 1
];

verificarConcorrencia(
    '8 entregas do mesmo UUID',
    $produtoB,
    array_fill(0, 8, $eventoB),
    '11.000',
    1
);
