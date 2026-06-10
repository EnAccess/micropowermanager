<?php

use App\Plugins\VodacomMzPaymentProvider\Http\Controllers\VodacomMzCredentialController;
use App\Plugins\VodacomMzPaymentProvider\Http\Controllers\VodacomMzTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('vodacom_mz')->group(function () {
    Route::prefix('credentials')->group(function () {
        Route::get('/', [VodacomMzCredentialController::class, 'show']);
        Route::put('/', [VodacomMzCredentialController::class, 'update']);
    });

    Route::prefix('transactions')
        ->middleware('auth:api-key')
        ->group(function () {
            Route::post('/validate', [VodacomMzTransactionController::class, 'validateTransaction']);
            Route::post('/process', [VodacomMzTransactionController::class, 'processTransaction']);
            Route::post('/query', [VodacomMzTransactionController::class, 'queryTransactionStatus']);
        });
});
