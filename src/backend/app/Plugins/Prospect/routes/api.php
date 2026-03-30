<?php

use App\Plugins\Prospect\Http\Controllers\ProspectCredentialController;
use App\Plugins\Prospect\Http\Controllers\ProspectSyncSettingController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'prospect'], function () {
    Route::group(['prefix' => 'credential'], function () {
        Route::get(
            '/',
            [ProspectCredentialController::class, 'show']
        );
        Route::put('/', [ProspectCredentialController::class, 'update']);
    });
    Route::group(['prefix' => 'prospect-setting'], function () {
        Route::group(['prefix' => 'sync-setting'], function () {
            Route::get(
                '/',
                [ProspectSyncSettingController::class, 'index']
            );
            Route::put(
                '/',
                [ProspectSyncSettingController::class, 'update']
            );
        });
    });
});
