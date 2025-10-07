<?php

use Illuminate\Support\Facades\Route;
use Inensus\OdysseyDataExport\Http\Controllers\OdysseyPaymentsController;

Route::prefix('/payments')
    ->group(function () {
        Route::get('/odyssey', [OdysseyPaymentsController::class, 'index']);
    });
