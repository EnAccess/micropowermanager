<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'textbee-sms-gateway'], function () {
    Route::group(['prefix' => 'credential'], function () {
        Route::get('/', 'TextbeeCredentialController@show');
        Route::put('/', 'TextbeeCredentialController@update');
    });
    Route::group(['prefix' => 'callback'], function () {
        Route::post('/{slug}/incoming-messages', 'TextbeeCallbackController@incoming');
    });
});
