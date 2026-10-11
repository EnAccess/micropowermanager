<?php

use App\Plugins\WhatsappMessaging\Http\Controllers\WhatsappContactController;
use App\Plugins\WhatsappMessaging\Http\Controllers\WhatsappTemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('whatsapp-messaging')->middleware('auth:api')->group(function () {
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
});
