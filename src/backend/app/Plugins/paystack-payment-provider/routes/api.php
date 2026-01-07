<?php

use Illuminate\Support\Facades\Route;
use Inensus\PaystackPaymentProvider\Http\Controllers\PaystackController;
use Inensus\PaystackPaymentProvider\Http\Controllers\PaystackCredentialController;
use Inensus\PaystackPaymentProvider\Http\Controllers\PaystackPublicController;

Route::prefix('paystack')->group(function () {
    // Credential management
    Route::get('/credential', [PaystackCredentialController::class, 'show']);
    Route::put('/credential', [PaystackCredentialController::class, 'update']);
    Route::get('/credential/public-urls', [PaystackCredentialController::class, 'generatePublicUrls']);
    Route::post('/credential/agent-payment-url', [PaystackCredentialController::class, 'generateAgentPaymentUrl']);

    // Transaction management
    Route::post('/transaction/initialize', [PaystackController::class, 'startTransaction']);
    Route::get('/transaction/verify/{reference}', [PaystackController::class, 'verifyTransaction']);
    Route::get('/transactions', [PaystackController::class, 'getTransactions']);
    Route::get('/transactions/{id}', [PaystackController::class, 'getTransaction']);
    Route::put('/transactions/{id}', [PaystackController::class, 'updateTransaction']);
    Route::delete('/transactions/{id}', [PaystackController::class, 'deleteTransaction']);

    // Webhook with company ID
    Route::post('/webhook/{companyId}', [PaystackController::class, 'webhookCallback']);

    // Public payment pages (no authentication required)
    Route::prefix('public')->group(function () {
        // New tokenized routes: use ?ct=<hashed company id> to avoid exposing company ID
        Route::get('/payment/{companyHash}', [PaystackPublicController::class, 'showPaymentForm']);
        Route::post('/payment/{companyHash}', [PaystackPublicController::class, 'initiatePayment']);
        Route::get('/result/{companyHash}', [PaystackPublicController::class, 'showResult']);
        Route::get('/verify/{companyHash}', [PaystackPublicController::class, 'verifyTransaction']);
        Route::post('/validate-meter/{companyHash}', [PaystackPublicController::class, 'validateMeter']);
    });
});
