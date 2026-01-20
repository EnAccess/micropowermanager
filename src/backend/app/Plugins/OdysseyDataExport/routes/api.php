<?php

use App\Plugins\OdysseyDataExport\Http\Controllers\OdysseyPaymentsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/odyssey')
    ->middleware('auth:api-key')
    ->group(function () {
        Route::get('/payments', [OdysseyPaymentsController::class, 'index']);
    });
