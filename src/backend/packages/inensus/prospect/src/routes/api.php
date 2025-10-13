<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'prospect'], function () {
    Route::group(['prefix' => 'credential'], function () {
        Route::get('/', 'ProspectCredentialController@show');
        Route::put('/', 'ProspectCredentialController@update');
    });
    Route::group(['prefix' => 'prospect-setting'], function () {
        Route::group(['prefix' => 'sync-setting'], function () {
            Route::put('/', 'ProspectSyncSettingController@update');
        });
    });
});
