<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'daly-bms'], function () {
    Route::group(['prefix' => 'daly-bms-credential'], function () {
        Route::get('/', 'DalyBmsCredentialController@show');
        Route::put('/', 'DalyBmsCredentialController@update');
    });
});
