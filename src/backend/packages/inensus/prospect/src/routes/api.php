<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'prospect'], function () {
    Route::group(['prefix' => 'credential'], function () {
        Route::get('/', 'ProspectCredentialController@show');
        Route::put('/', 'ProspectCredentialController@update');
    });
});
