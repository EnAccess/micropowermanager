<?php

use App\Plugins\CalinMeterV2\Http\Controllers\CalinV2CredentialController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'calin-meter-v2'], function () {
    Route::group(['prefix' => 'credential'], function () {
        Route::get('/', [CalinV2CredentialController::class, 'show']);
        Route::put('/', [CalinV2CredentialController::class, 'update']);
        Route::post('/check', [CalinV2CredentialController::class, 'check']);
    });
});
