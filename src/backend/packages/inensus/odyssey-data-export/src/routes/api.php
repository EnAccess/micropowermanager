<?php

use Illuminate\Support\Facades\Route;
use Inensus\OdysseyDataExport\Http\Controllers\OdysseyPaymentsController;

Route::prefix('/odyssey')
    ->middleware('auth:api-key')
    ->group(function () {
        Route::get('/payments', [OdysseyPaymentsController::class, 'index']);
    });
