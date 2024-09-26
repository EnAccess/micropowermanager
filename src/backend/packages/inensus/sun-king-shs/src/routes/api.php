<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'sun-king-shs'], function () {
    Route::group(['prefix' => 'sun-king-credential'], function () {
        Route::get('/', 'SunKingCredentialController@show');
        Route::put('/', 'SunKingCredentialController@update');
    });
});
