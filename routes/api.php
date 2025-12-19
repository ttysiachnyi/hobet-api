<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CasinoController;

Route::post('/casino/spin', [CasinoController::class, 'spin']);
Route::post('/casino/bonus', [CasinoController::class, 'bonus']);
