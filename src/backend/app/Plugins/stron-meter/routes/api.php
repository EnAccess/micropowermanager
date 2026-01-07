<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'stron-meters'], function () {
    Route::group(['prefix' => 'stron-credential'], function () {
        Route::get('/', 'StronCredentialController@show');
        Route::put('/', 'StronCredentialController@update');
    });
});
