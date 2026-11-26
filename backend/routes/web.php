<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\UsuarioController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/php/csrf.php', [AuthController::class, 'csrf']);

Route::post('/php/usuario_login.php', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::get('/php/valida_sessao.php', [AuthController::class, 'session']);

Route::post('/php/cliente_logoff.php', [AuthController::class, 'logout']);

Route::middleware('auth')->group(function () {
    Route::get(
        '/php/produto_get.php',
        [ProdutoController::class, 'index']
    )->middleware('can:produtos.visualizar');
    Route::post(
        '/php/produto_novo.php',
        [ProdutoController::class, 'store']
    )->middleware('can:produtos.gravar');

    Route::post(
        '/php/produto_alterar.php',
        [ProdutoController::class, 'update']
    )->middleware('can:produtos.gravar');

    Route::post(
        '/php/produto_excluir.php',
        [ProdutoController::class, 'destroy']
    )->middleware('can:produtos.excluir');
    Route::post(
        '/php/produto_restaurar.php',
        [ProdutoController::class, 'restore']
    )->middleware('can:produtos.restaurar');
    Route::post(
        '/php/usuario_novo.php',
        [AuthController::class, 'register']
    )->middleware(['can:usuarios.criar', 'throttle:5,1']);
    Route::get(
        '/php/usuarios_get.php',
        [UsuarioController::class, 'index']
    )->middleware('can:usuarios.gerenciar');
    Route::post(
        '/php/usuario_bloquear.php',
        [UsuarioController::class, 'bloquear']
    )->middleware('can:usuarios.gerenciar');
    Route::post(
        '/php/usuario_desbloquear.php',
        [UsuarioController::class, 'desbloquear']
    )->middleware('can:usuarios.gerenciar');
    Route::post(
        '/php/usuario_situacao.php',
        [UsuarioController::class, 'alterarSituacao']
    )->middleware('can:usuarios.gerenciar');
});
