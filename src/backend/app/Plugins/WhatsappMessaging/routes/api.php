<?php

use App\Plugins\WhatsappMessaging\Http\Controllers\WhatsappContactController;
use App\Plugins\WhatsappMessaging\Http\Controllers\WhatsappCredentialController;
use App\Plugins\WhatsappMessaging\Http\Controllers\WhatsappMessageController;
use App\Plugins\WhatsappMessaging\Http\Controllers\WhatsappTemplateController;
use App\Plugins\WhatsappMessaging\Http\Controllers\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('whatsapp-messaging')->middleware('auth:api')->group(function () {
    Route::prefix('credential')->group(function () {
        Route::get('/', [WhatsappCredentialController::class, 'show']);
        Route::put('/', [WhatsappCredentialController::class, 'update']);
        Route::post('/test', [WhatsappCredentialController::class, 'testConnection']);
    });

    Route::prefix('templates')->group(function () {
        Route::get('/', [WhatsappTemplateController::class, 'index']);
        Route::put('/{template}', [WhatsappTemplateController::class, 'update']);
    });

    Route::prefix('contacts')->group(function () {
        Route::get('/', [WhatsappContactController::class, 'index']);
        Route::post('/{person}/opt-in', [WhatsappContactController::class, 'optIn']);
        Route::post('/{person}/opt-out', [WhatsappContactController::class, 'optOut']);
        Route::get('/{contact}/consent-logs', [WhatsappContactController::class, 'consentLogs']);
    });

    Route::prefix('messages')->group(function () {
        Route::get('/', [WhatsappMessageController::class, 'index']);
        Route::get('/summary', [WhatsappMessageController::class, 'summary']);
        Route::get('/{whatsappMessage}', [WhatsappMessageController::class, 'show']);
    });
});

// Public webhook endpoints (no auth: called by Meta)
Route::get('/whatsapp-messaging/webhook/{companyId}', [WhatsappWebhookController::class, 'verify']);
Route::post('/whatsapp-messaging/webhook/{companyId}', [WhatsappWebhookController::class, 'receive']);
