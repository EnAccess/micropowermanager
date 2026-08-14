<?php

use App\Plugins\FlutterwavePaymentProvider\Http\Controllers\FlutterwaveController;
use App\Plugins\FlutterwavePaymentProvider\Http\Controllers\FlutterwaveCredentialController;
use App\Plugins\FlutterwavePaymentProvider\Http\Controllers\FlutterwavePublicController;
use Illuminate\Support\Facades\Route;

Route::prefix('flutterwave')->group(function () {
    // Credential management
    Route::get('/credential', [FlutterwaveCredentialController::class, 'show']);
    Route::put('/credential', [FlutterwaveCredentialController::class, 'update']);
    Route::get('/credential/public-urls', [FlutterwaveCredentialController::class, 'generatePublicUrls']);
    Route::post('/credential/agent-payment-url', [FlutterwaveCredentialController::class, 'generateAgentPaymentUrl']);

    // Transaction management
    Route::post('/transaction/initialize', [FlutterwaveController::class, 'initializeTransaction']);
    Route::get('/transaction/verify/{transactionId}', [FlutterwaveController::class, 'verifyTransaction']);
    Route::get('/transactions', [FlutterwaveController::class, 'getTransactions']);
    Route::get('/transactions/{id}', [FlutterwaveController::class, 'getTransaction']);
    Route::put('/transactions/{id}', [FlutterwaveController::class, 'updateTransaction']);
    Route::delete('/transactions/{id}', [FlutterwaveController::class, 'deleteTransaction']);

    // Webhook with company ID
    Route::post('/webhook/{companyId}', [FlutterwaveController::class, 'webhookCallback']);

    // Public payment pages (no authentication required)
    Route::prefix('public')->group(function () {
        // Tokenized routes: use ?ct=<hashed company id> to avoid exposing company ID
        Route::get('/payment/{companyHash}', [FlutterwavePublicController::class, 'showPaymentForm']);
        Route::post('/payment/{companyHash}', [FlutterwavePublicController::class, 'initiatePayment']);
        Route::get('/result/{companyHash}', [FlutterwavePublicController::class, 'showResult']);
        Route::get('/verify/{companyHash}', [FlutterwavePublicController::class, 'verifyTransaction']);
        Route::post('/validate-meter/{companyHash}', [FlutterwavePublicController::class, 'validateMeter']);
    });
});
