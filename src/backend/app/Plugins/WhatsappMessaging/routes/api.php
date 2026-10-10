<?php

use App\Plugins\WhatsappMessaging\Http\Controllers\WhatsappContactController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'whatsapp-messaging'], function () {
    Route::group(['prefix' => 'contacts', 'middleware' => 'auth:api'], function () {
        Route::get('/', [WhatsappContactController::class, 'index']);
        Route::post('/{person}/opt-in', [WhatsappContactController::class, 'optIn']);
        Route::post('/{person}/opt-out', [WhatsappContactController::class, 'optOut']);
        Route::get('/{contact}/consent-logs', [WhatsappContactController::class, 'consentLogs']);
    });
});
