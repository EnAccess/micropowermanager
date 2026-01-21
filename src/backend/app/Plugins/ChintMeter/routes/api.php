<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'chint-meters'], function () {
    Route::group(['prefix' => 'chint-credential'], function () {
        Route::get('/', 'ChintCredentialController@show');
        Route::put('/', 'ChintCredentialController@update');
    });
});
