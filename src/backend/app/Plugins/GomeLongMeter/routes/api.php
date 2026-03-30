<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'gome-long-meters'], function () {
    Route::group(['prefix' => 'gome-long-credential'], function () {
        Route::get('/', 'GomeLongCredentialController@show');
        Route::put('/', 'GomeLongCredentialController@update');
    });
});
