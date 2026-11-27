<?php

use App\Plugins\AirtelSmsGateway\Http\Controllers\AirtelSmsCredentialController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'airtel-sms-gateway'], function () {
    Route::group(['prefix' => 'credential'], function () {
        Route::get('/', [AirtelSmsCredentialController::class, 'show']);
        Route::put('/', [AirtelSmsCredentialController::class, 'update']);
    });
});
