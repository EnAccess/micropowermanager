<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AddressController;

/* Address */
Route::middleware('jwt.verify')
    ->prefix('addresses')
    ->group(function () {
        Route::get('/', [AddressController::class, 'index']);
        Route::get('/{id}', [AddressController::class, 'show']);
        Route::post('/', [AddressController::class, 'store']);
        Route::put('/{id}', [AddressController::class, 'update']);
    });
