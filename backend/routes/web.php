<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProdutoController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/php/csrf.php', [AuthController::class, 'csrf']);

Route::post('/php/usuario_novo.php', [AuthController::class, 'register'])->middleware('throttle:5,1');

Route::post('/php/usuario_login.php', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::get('/php/valida_sessao.php', [AuthController::class, 'session']);

Route::post('/php/cliente_logoff.php', [AuthController::class, 'logout']);

Route::middleware('auth')->group(function () {
    Route::get(
        '/php/produto_get.php',
        [ProdutoController::class, 'index']
    );
    Route::post(
        '/php/produto_novo.php',
        [ProdutoController::class, 'store']
    );

    Route::post(
        '/php/produto_alterar.php',
        [ProdutoController::class, 'update']
    );

    Route::post(
        '/php/produto_excluir.php',
        [ProdutoController::class, 'destroy']
    );
});
