<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MySqlTargetTest extends TestCase
{
    public function test_mysql_de_testes_esta_isolado(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Teste exclusivo do MySQL de integração.');
        }
        $this->assertSame(
            'testing',
            app()->environment()
        );

        $conexao = DB::connection();

        $this->assertSame(
            'mysql',
            $conexao->getDriverName()
        );

        $this->assertSame(
            'db_test',
            $conexao->getConfig('host')
        );

        $this->assertSame(
            'gennus_test',
            $conexao->getDatabaseName()
        );

        $bancoReal = DB::selectOne(
            'SELECT DATABASE() AS nome'
        );

        $this->assertSame(
            'gennus_test',
            $bancoReal->nome
        );
    }
}
