<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EstoqueInternoController;
use App\Http\Middleware\ValidarTokenGenziot;
Route::post('/internal/v1/estoque/movimentacoes', [EstoqueInternoController::class, 'store'])->middleware([ValidarTokenGenziot::class, 'throttle:60,1']);
