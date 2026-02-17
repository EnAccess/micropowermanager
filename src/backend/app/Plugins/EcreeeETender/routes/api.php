<?php

use App\Plugins\EcreeeETender\Http\Controllers\EcreeeMeterDataController;
use Illuminate\Support\Facades\Route;

Route::prefix('/ecreee-e-tender')
    ->middleware('auth:api-key')
    ->group(function () {
        Route::get('/ecreee-meter-data', [EcreeeMeterDataController::class, 'index']);
    });
