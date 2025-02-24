<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'africas-talking'], function () {
    Route::group(['prefix' => 'credential'], function () {
        Route::get('/', 'AfricasTalkingCredentialController@show');
        Route::put('/', 'AfricasTalkingCredentialController@update');
    });
    Route::group(['prefix' => 'callback'], function () {
        Route::post('/{slug}/incoming-messages', 'AfricasTalkingCallbackController@incoming');
        Route::post('/{slug}/delivery-reports', 'AfricasTalkingCallbackController@delivery');
    });
});
