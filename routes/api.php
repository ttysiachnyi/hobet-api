<?php

use App\Http\Middleware\MiniJwtAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CasinoController;

Route::post('/casino/spin', [CasinoController::class, 'spin'])
    ->middleware(MiniJwtAuth::class);
Route::post('/casino/bonus', [CasinoController::class, 'bonus']);
Route::get('/ping', fn () => response()->json(['ok' => true])); // testing purposes
