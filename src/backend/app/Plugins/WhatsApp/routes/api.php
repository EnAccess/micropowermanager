<?php

use App\Plugins\WhatsApp\Http\Controllers\WhatsAppCredentialController;
use App\Plugins\WhatsApp\Http\Controllers\WhatsAppTestMessageController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'whatsapp'], function () {
    Route::group(['prefix' => 'credential'], function () {
        Route::get('/', [WhatsAppCredentialController::class, 'show']);
        Route::put('/', [WhatsAppCredentialController::class, 'update']);
    });

    Route::post('/test-message', [WhatsAppTestMessageController::class, 'sendTestMessage']);
});
