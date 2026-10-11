<?php

use App\Plugins\WhatsappMessaging\Http\Controllers\WhatsappCredentialController;
use App\Plugins\WhatsappMessaging\Http\Controllers\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'whatsapp-messaging', 'middleware' => 'auth:api'], function () {
    Route::get('/credential', [WhatsappCredentialController::class, 'show']);
    Route::put('/credential', [WhatsappCredentialController::class, 'update']);
    Route::post('/credential/test', [WhatsappCredentialController::class, 'testConnection']);
});

Route::get('/whatsapp-messaging/webhook/{companyId}', [WhatsappWebhookController::class, 'verify']);
Route::post('/whatsapp-messaging/webhook/{companyId}', [WhatsappWebhookController::class, 'receive']);
