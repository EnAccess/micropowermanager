<?php

use App\Plugins\WhatsappMessaging\Http\Controllers\WhatsappTemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('whatsapp-messaging')->group(function () {
    Route::prefix('templates')
        ->middleware('auth:api')
        ->group(function () {
            Route::get('/', [WhatsappTemplateController::class, 'index']);
            Route::put('/{template}', [WhatsappTemplateController::class, 'update']);
        });
});
