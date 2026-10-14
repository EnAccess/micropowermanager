<?php

use App\Plugins\WhatsApp\Http\Controllers\WhatsAppCredentialController;
use App\Plugins\WhatsApp\Http\Controllers\WhatsAppTestMessageController;
use App\Plugins\WhatsApp\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'whatsapp'], function () {
    Route::get('/webhook/{companyId}', [WhatsAppWebhookController::class, 'verify'])->whereNumber('companyId');
    Route::post('/webhook/{companyId}', [WhatsAppWebhookController::class, 'receive'])->whereNumber('companyId');
    Route::group(['prefix' => 'credential'], function () {
        Route::get('/', [WhatsAppCredentialController::class, 'show']);
        Route::put('/', [WhatsAppCredentialController::class, 'update']);
    });

    Route::post('/test-message', [WhatsAppTestMessageController::class, 'sendTestMessage']);
});
