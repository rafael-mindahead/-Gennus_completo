<?php

use App\Support\ApiEnvelope;
use Illuminate\Support\Facades\Route;

// Does not replace any /php/*.php legacy endpoint.
Route::get('/v1/health', fn () => response()->json(ApiEnvelope::ok(
    ['service' => 'gennus-api', 'version' => '2.0-dev'],
    'Gennus 2.0 API em desenvolvimento.',
)));
