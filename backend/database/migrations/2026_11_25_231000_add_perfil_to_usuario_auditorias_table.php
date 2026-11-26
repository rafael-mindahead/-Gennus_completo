<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario_auditorias', function (Blueprint $table) {
            $table->string('perfil_anterior', 20)
                ->nullable();

            $table->string('perfil_novo', 20)
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('usuario_auditorias', function (Blueprint $table) {
            $table->dropColumn([
                'perfil_anterior',
                'perfil_novo',
            ]);
        });
    }
};
