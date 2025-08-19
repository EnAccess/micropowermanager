<?php

use Illuminate\Support\Facades\Route;
use Inensus\PaystackPaymentProvider\Http\Controllers\PaystackController;
use Inensus\PaystackPaymentProvider\Http\Controllers\PaystackCredentialController;

Route::prefix('paystack')->group(function () {
    // Credential management
    Route::get('/credential', [PaystackCredentialController::class, 'show']);
    Route::put('/credential', [PaystackCredentialController::class, 'update']);

    // Transaction management
    Route::post('/transaction/initialize', [PaystackController::class, 'startTransaction']);
    Route::get('/transaction/verify/{reference}', [PaystackController::class, 'verifyTransaction']);
    Route::get('/transactions', [PaystackController::class, 'getTransactions']);
    Route::get('/transactions/{id}', [PaystackController::class, 'getTransaction']);
    Route::put('/transactions/{id}', [PaystackController::class, 'updateTransaction']);
    Route::delete('/transactions/{id}', [PaystackController::class, 'deleteTransaction']);

    // Webhook
    Route::post('/webhook', [PaystackController::class, 'webhookCallback']);
});
