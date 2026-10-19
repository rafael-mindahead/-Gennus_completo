<?php
use Illuminate\Support\Facades\Route;

// Does not replace any /php/*.php legacy endpoint.
Route::get('/v1/health', fn () => response()->json([
    'status' => 'ok',
    'mensagem' => 'Gennus 2.0 API em desenvolvimento.',
    'data' => ['service' => 'gennus-api', 'version' => '2.0-dev'],
]));
