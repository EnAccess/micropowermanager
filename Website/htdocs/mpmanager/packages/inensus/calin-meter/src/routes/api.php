<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'calin-meters'], function () {
    Route::group(['prefix' => 'calin-credential'], function () {
        Route::get('/', 'CalinCredentialController@show');
        Route::put('/', 'CalinCredentialController@update');
    });
});
