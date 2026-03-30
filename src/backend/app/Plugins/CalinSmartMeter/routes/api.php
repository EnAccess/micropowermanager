<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'calin-smart-meters'], function () {
    Route::group(['prefix' => 'calin-smart-credential'], function () {
        Route::get('/', 'CalinSmartCredentialController@show');
        Route::put('/', 'CalinSmartCredentialController@update');
    });
});
