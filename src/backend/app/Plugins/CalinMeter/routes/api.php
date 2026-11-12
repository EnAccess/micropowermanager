<?php

use App\Plugins\CalinMeter\Http\Controllers\CalinCredentialController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'calin-meters'], function () {
    Route::group(['prefix' => 'calin-credential'], function () {
        Route::get('/', [CalinCredentialController::class, 'show']);
        Route::put('/', [CalinCredentialController::class, 'update']);
        Route::post('/check', [CalinCredentialController::class, 'check']);
    });
});
