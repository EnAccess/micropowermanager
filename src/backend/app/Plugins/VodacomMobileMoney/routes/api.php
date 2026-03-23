<?php

use App\Plugins\VodacomMobileMoney\Http\Controllers\VodacomTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('vodacom')->group(function () {
    Route::prefix('transactions')
        ->middleware('auth:api-key')
        ->group(function () {
            Route::post('/validate', [VodacomTransactionController::class, 'validateTransaction']);
            Route::post('/process', [VodacomTransactionController::class, 'processTransaction']);
            Route::post('/query', [VodacomTransactionController::class, 'queryTransactionStatus']);
        });
});
