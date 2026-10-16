<?php

use App\Plugins\OdysseyDataExport\Http\Controllers\OdysseyPaymentsController;
use App\Plugins\OdysseyDataExport\Http\Controllers\OdysseySettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('/odyssey')
    ->middleware('auth:api-key')
    ->group(function () {
        Route::get('/payments', [OdysseyPaymentsController::class, 'index']);
    });

Route::prefix('/odyssey-data-export')
    ->group(function () {
        Route::get('/settings', [OdysseySettingController::class, 'show']);
        Route::put('/settings', [OdysseySettingController::class, 'update']);
    });
