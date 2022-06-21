<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'kelin-meters'], function () {
    Route::group(['prefix' => 'kelin-credential'], function () {
        Route::get('/', 'KelinCredentialController@show');
        Route::put('/', 'KelinCredentialController@update');
    });
    Route::group(['prefix' => 'kelin-customer'], function () {
        Route::get('/', 'KelinCustomerController@index');
        Route::get('/sync', 'KelinCustomerController@sync');
        Route::get('/sync-check', 'KelinCustomerController@checkSync');
        Route::get('/advanced/search', 'KelinCustomerController@search');
    });
    Route::group(['prefix' => 'kelin-meter'], function () {
        Route::get('/', 'KelinMeterController@index');
        Route::get('/sync', 'KelinMeterController@sync');
        Route::get('/sync-check', 'KelinMeterController@checkSync');
        Route::group(['prefix' => 'daily-consumptions'], function () {
            Route::get('/{meter}', 'KelinDailyConsumptionController@index');
        });
        Route::group(['prefix' => 'minutely-consumptions'], function () {
            Route::get('/{meter}', 'KelinMinutelyConsumptionController@index');
        });
        Route::group(['prefix' => 'status'], function () {
            Route::get('/{meter}', 'KelinStatusController@show');
            Route::put('/{meter}', 'KelinStatusController@update');
        });
    });
    Route::group(['prefix' => 'kelin-setting'], function () {
        Route::get('/', 'KelinSettingController@index');
        Route::group(['prefix' => 'sync-setting'], function () {
            Route::put('/', 'KelinSyncSettingController@update');
        });
    });
});
