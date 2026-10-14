<?php

use App\Plugins\WhatsApp\Http\Controllers\WhatsAppCredentialController;
use App\Plugins\WhatsApp\Http\Controllers\WhatsAppMessageController;
use App\Plugins\WhatsApp\Http\Controllers\WhatsAppStatusController;
use App\Plugins\WhatsApp\Http\Controllers\WhatsAppTemplateController;
use App\Plugins\WhatsApp\Http\Controllers\WhatsAppTestMessageController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'whatsapp'], function () {
    Route::get('/credential', [WhatsAppCredentialController::class, 'show']);
    Route::put('/credential', [WhatsAppCredentialController::class, 'update']);

    Route::get('/status', [WhatsAppStatusController::class, 'show']);

    Route::get('/templates', [WhatsAppTemplateController::class, 'index']);
    Route::post('/templates', [WhatsAppTemplateController::class, 'store']);
    Route::put('/templates/{template}', [WhatsAppTemplateController::class, 'update']);

    Route::get('/messages', [WhatsAppMessageController::class, 'index']);

    Route::post('/test-message', [WhatsAppTestMessageController::class, 'sendTestMessage']);
});
