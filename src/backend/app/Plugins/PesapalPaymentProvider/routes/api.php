<?php

use App\Plugins\PesapalPaymentProvider\Http\Controllers\PesapalController;
use App\Plugins\PesapalPaymentProvider\Http\Controllers\PesapalCredentialController;
use App\Plugins\PesapalPaymentProvider\Http\Controllers\PesapalPublicController;
use Illuminate\Support\Facades\Route;

Route::prefix('pesapal')->group(function () {
    Route::get('/credential', [PesapalCredentialController::class, 'show']);
    Route::put('/credential', [PesapalCredentialController::class, 'update']);
    Route::get('/credential/public-urls', [PesapalCredentialController::class, 'generatePublicUrls']);
    Route::post('/credential/agent-payment-url', [PesapalCredentialController::class, 'generateAgentPaymentUrl']);

    Route::post('/transaction/initialize', [PesapalController::class, 'initializeTransaction']);
    Route::get('/transaction/verify/{orderTrackingId}', [PesapalController::class, 'verifyTransaction']);
    Route::get('/transactions', [PesapalController::class, 'getTransactions']);
    Route::get('/transactions/{id}', [PesapalController::class, 'getTransaction']);
    Route::put('/transactions/{id}', [PesapalController::class, 'updateTransaction']);
    Route::delete('/transactions/{id}', [PesapalController::class, 'deleteTransaction']);

    // PesaPal IPN (Instant Payment Notification) — unsigned; service re-queries status.
    Route::match(['GET', 'POST'], '/ipn/{companyId}', [PesapalPublicController::class, 'handleIpn']);

    Route::prefix('public')->group(function () {
        Route::get('/payment/{companyHash}', [PesapalPublicController::class, 'showPaymentForm']);
        Route::post('/payment/{companyHash}', [PesapalPublicController::class, 'initiatePayment']);
        Route::get('/result/{companyHash}', [PesapalPublicController::class, 'showResult']);
        Route::get('/verify/{companyHash}', [PesapalPublicController::class, 'verifyTransaction']);
        Route::post('/validate-meter/{companyHash}', [PesapalPublicController::class, 'validateMeter']);
    });
});
