<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';

$app->make(
    Illuminate\Contracts\Console\Kernel::class
)->bootstrap();

use App\Models\Produto;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

if (
    config('database.default') !== 'mysql' ||
    config('database.connections.mysql.database') !== 'gennus_v2_test'
) {
    throw new RuntimeException(
        'Este teste só pode executar em gennus_v2_test.'
    );
}

$produto = Produto::firstOrCreate(
    ['codigo_barras' => '7900000000001'],
    [
        'nome' => 'Produto Teste Concorrencia',
        'categoria' => 'Testes',
        'unidade' => 'un',
        'custo' => '10.00',
        'preco' => '20.00',
        'estoque' => '10.000'
    ]
);

config([
    'database.connections.mysql_probe' =>
        config('database.connections.mysql')
]);

DB::purge('mysql_probe');

$conexaoA = DB::connection('mysql');
$conexaoB = DB::connection('mysql_probe');

$conexaoA->beginTransaction();

try {
    $conexaoA->table('produtos')
        ->where('id', $produto->id)
        ->lockForUpdate()
        ->first();

    echo "Conexao A: bloqueio adquirido.\n";

    try {
        $conexaoB->table('produtos')
            ->where('id', $produto->id)
            ->lock('FOR UPDATE NOWAIT')
            ->first();

        throw new RuntimeException(
            'Falha: a conexao B conseguiu adquirir o bloqueio.'
        );

    } catch (QueryException $e) {
        $codigo = (int) ($e->errorInfo[1] ?? 0);

        if ($codigo !== 3572) {
            throw $e;
        }

        echo "Conexao B: bloqueio recusado corretamente.\n";
        echo "PASSOU: lock exclusivo confirmado.\n";
    }

} finally {
    $conexaoA->rollBack();
    DB::disconnect('mysql_probe');
}
