<?php

use App\Plugins\EgoSmsGateway\Http\Controllers\EgoSmsCredentialController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ego-sms-gateway'], function () {
    Route::group(['prefix' => 'credential'], function () {
        Route::get('/', [EgoSmsCredentialController::class, 'show']);
        Route::put('/', [EgoSmsCredentialController::class, 'update']);
    });
});
