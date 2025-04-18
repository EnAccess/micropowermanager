<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CityController;

/* City */
Route::middleware('jwt.verify')
    ->prefix('cities')
    ->group(function () {
        Route::get('/', [CityController::class, 'index']);
        Route::get('/{cityId}', [CityController::class, 'show'])->where('id', '[0-9]+');
        Route::post('/', [CityController::class, 'store']);
        Route::put('/{cityId}', [CityController::class, 'update']);
    });
