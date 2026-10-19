<?php
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'status' => 'ok',
    'mensagem' => 'Backend independente do Gennus 2.0.',
    'data' => [],
]));
