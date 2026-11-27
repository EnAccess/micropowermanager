<?php

use App\Plugins\AirtelPaymentProvider\Http\Controllers\AirtelCallbackController;
use App\Plugins\AirtelPaymentProvider\Http\Controllers\AirtelCredentialController;
use App\Plugins\AirtelPaymentProvider\Http\Middleware\VerifyAirtelToken;
use Illuminate\Support\Facades\Route;

Route::prefix('airtel-payment-provider')->group(function () {
    Route::prefix('credential')->group(function () {
        Route::get('/', [AirtelCredentialController::class, 'show']);
        Route::put('/', [AirtelCredentialController::class, 'update']);
    });

    Route::prefix('callback/{companyId}')
        ->whereNumber('companyId')
        ->middleware(VerifyAirtelToken::class)
        ->group(function () {
            Route::post('/validation', [AirtelCallbackController::class, 'validation']);
            Route::post('/process', [AirtelCallbackController::class, 'process']);
            Route::post('/enquiry', [AirtelCallbackController::class, 'enquiry']);
        });
});
