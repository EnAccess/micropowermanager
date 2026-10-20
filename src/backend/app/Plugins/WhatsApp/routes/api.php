<?php

use App\Plugins\WhatsApp\Http\Controllers\WhatsAppCredentialController;
use App\Plugins\WhatsApp\Http\Controllers\WhatsAppMessageController;
use App\Plugins\WhatsApp\Http\Controllers\WhatsAppStatusController;
use App\Plugins\WhatsApp\Http\Controllers\WhatsAppTemplateController;
use App\Plugins\WhatsApp\Http\Controllers\WhatsAppTestMessageController;
use App\Plugins\WhatsApp\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'whatsapp'], function () {
    Route::get('/status', [WhatsAppStatusController::class, 'show']);
    Route::get('/messages', [WhatsAppMessageController::class, 'index'])->middleware('auth:api');
    Route::get('/webhook/{companyId}', [WhatsAppWebhookController::class, 'verify'])->whereNumber('companyId');
    Route::post('/webhook/{companyId}', [WhatsAppWebhookController::class, 'receive'])->whereNumber('companyId');
    Route::group(['prefix' => 'credential'], function () {
        Route::get('/', [WhatsAppCredentialController::class, 'show']);
        Route::put('/', [WhatsAppCredentialController::class, 'update']);
    });

    Route::get('/templates', [WhatsAppTemplateController::class, 'index']);
    Route::post('/templates', [WhatsAppTemplateController::class, 'store']);
    Route::put('/templates/{template}', [WhatsAppTemplateController::class, 'update']);

    Route::post('/test-message', [WhatsAppTestMessageController::class, 'sendTestMessage']);
});
