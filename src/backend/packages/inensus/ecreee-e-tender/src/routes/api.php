<?php

use Illuminate\Support\Facades\Route;
use Inensus\EcreeeETender\Http\Controllers\EcreeeMeterDataController;


Route::prefix('/ecreee-e-tender')
    ->middleware('auth:api-key')
    ->group(function () {
        Route::get('/ecreee-meter-data', [EcreeeMeterDataController::class, 'index']);
    });
