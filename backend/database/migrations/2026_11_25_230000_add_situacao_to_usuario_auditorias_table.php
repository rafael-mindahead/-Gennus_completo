<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario_auditorias', function (Blueprint $table) {
            $table->string('situacao_anterior', 20)
                ->nullable();

            $table->string('situacao_nova', 20)
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('usuario_auditorias', function (Blueprint $table) {
            $table->dropColumn([
                'situacao_anterior',
                'situacao_nova',
            ]);
        });
    }
};
