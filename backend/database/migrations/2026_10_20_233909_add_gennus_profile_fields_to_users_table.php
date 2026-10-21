<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table){
            $table->string('sobrenome', 120)
                ->nullable()
                ->after('name');

            $table->string('telefone', 20)
                ->nullable()
                ->after('email');

            $table->string('cpf_cnpj', 20)
                ->nullable()
                ->after('telefone');
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['cpf_cnpj']);

            $table->dropColumn([
                'sobrenome',
                'telefone',
                'cpf_cnpj'
            ]);
        });
    }
};
