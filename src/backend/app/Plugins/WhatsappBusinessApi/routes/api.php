<?php

use App\Plugins\WhatsappBusinessApi\Http\Controllers\WhatsappCredentialController;
use App\Plugins\WhatsappBusinessApi\Http\Controllers\WhatsappOptInController;
use App\Plugins\WhatsappBusinessApi\Http\Controllers\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'whatsapp-business-api'], function () {
    Route::get('/credentials', [WhatsappCredentialController::class, 'get']);
    Route::put('/credentials', [WhatsappCredentialController::class, 'update']);
    Route::get('/opt-in', [WhatsappOptInController::class, 'index']);
    Route::get('/opt-in/stats', [WhatsappOptInController::class, 'getStats']);
    Route::post('/opt-in/bulk', [WhatsappOptInController::class, 'bulk']);
    Route::get('/opt-in/{personId}', [WhatsappOptInController::class, 'get']);
    Route::put('/opt-in/{personId}', [WhatsappOptInController::class, 'update']);
    Route::post('/sync-number', [WhatsappOptInController::class, 'syncNumber']);

    Route::group(['prefix' => 'webhook'], function () {
        Route::get('/{companyId}', [WhatsappWebhookController::class, 'verify']);
        Route::post('/{companyId}', [WhatsappWebhookController::class, 'handle']);
    });

    Route::group(['prefix' => 'callback/{companyId}'], function () {
        Route::get('/webhook', [WhatsappWebhookController::class, 'verify']);
        Route::post('/webhook', [WhatsappWebhookController::class, 'handle']);
    });
});
