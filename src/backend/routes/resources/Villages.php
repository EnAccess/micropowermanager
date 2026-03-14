<?php

use App\Http\Controllers\VillageController;
use Illuminate\Support\Facades\Route;

/* Village */
Route::middleware('jwt.verify')
    ->prefix('villages')
    ->group(function () {
        Route::get('/', [VillageController::class, 'index']);
        Route::get('/{villageId}', [VillageController::class, 'show'])->where('villageId', '[0-9]+');
        Route::post('/', [VillageController::class, 'store'])->middleware('permission:settings');
        Route::put('/{villageId}', [VillageController::class, 'update'])->middleware('permission:settings');
    });
