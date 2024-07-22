<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'steama-meters'], function () {
    Route::group(['prefix' => 'steama-credential'], function () {
        Route::get('/', 'SteamaCredentialController@show');
        Route::put('/', 'SteamaCredentialController@update');
    });
    Route::group(['prefix' => 'steama-site'], function () {
        Route::get('/', 'SteamaSiteController@index');
        Route::get('/sync', 'SteamaSiteController@sync');
        Route::get('/sync-check', 'SteamaSiteController@checkSync');
        Route::get('/count', 'SteamaSiteController@count');
        Route::get('/location', 'SteamaSiteController@location');
    });
    Route::group(['prefix' => 'steama-customer'], function () {
        Route::get('/', 'SteamaCustomerController@index');
        Route::get('/sync', 'SteamaCustomerController@sync');
        Route::get('/sync-check', 'SteamaCustomerController@checkSync');
        Route::get('/count', 'SteamaCustomerController@count');
        Route::put('/{customer}', 'SteamaCustomerController@update');
        Route::get('/{customerId}', 'SteamaCustomerController@get');
        Route::get('/advanced/search', 'SteamaCustomerController@search');
    });
    Route::group(['prefix' => 'steama-meter'], function () {
        Route::get('/', 'SteamaMeterController@index');
        Route::get('/sync', 'SteamaMeterController@sync');
        Route::get('/sync-check', 'SteamaMeterController@checkSync');
        Route::get('/count', 'SteamaMeterController@count');
    });
    Route::group(['prefix' => 'steama-agent'], function () {
        Route::get('/', 'SteamaAgentController@index');
        Route::get('/sync', 'SteamaAgentController@sync');
        Route::get('/sync-check', 'SteamaAgentController@checkSync');
        Route::get('/count', 'SteamaAgentController@count');
    });
    Route::group(['prefix' => 'steama-transaction'], function () {
        Route::get('/{customer}', 'SteamaTransactionController@index');
    });
    Route::group(['prefix' => 'steama-setting'], function () {
        Route::get('/', 'SteamaSettingController@index');
        Route::group(['prefix' => 'sms-setting'], function () {
            Route::put('/', 'SteamaSmsSettingController@update');
            // Sms
            Route::group(['prefix' => 'sms-body'], static function () {
                Route::get('/', 'SteamaSmsBodyController@index');
                Route::put('/', 'SteamaSmsBodyController@update');
            });
            Route::group(['prefix' => 'sms-variable-default-value'], static function () {
                Route::get('/', 'SteamaSmsVariableDefaultValueController@index');
            });
        });
        Route::group(['prefix' => 'sync-setting'], function () {
            Route::put('/', 'SteamaSyncSettingController@update');
        });
        Route::group(['prefix' => 'feedback-word'], function () {
            Route::get('/', 'SteamaSmsFeedbackController@index');
            Route::put('/{smsFeedbackWord}', 'SteamaSmsFeedbackController@update');
        });
    });
});
