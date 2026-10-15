<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'whatsapp'], function () {
    Route::group(['prefix' => 'credential'], function () {
        Route::get('/', 'WhatsappCredentialController@show');
        Route::put('/', 'WhatsappCredentialController@update');
        Route::post('/test', 'WhatsappCredentialController@test');
        Route::post('/send-test', 'WhatsappCredentialController@sendTest');
    });

    Route::get('/messages', 'WhatsappMessageController@index');

    Route::group(['prefix' => 'opt-ins'], function () {
        Route::get('/', 'WhatsappOptInController@index');
        Route::post('/', 'WhatsappOptInController@store');
        Route::delete('/{id}', 'WhatsappOptInController@destroy');
    });
});
