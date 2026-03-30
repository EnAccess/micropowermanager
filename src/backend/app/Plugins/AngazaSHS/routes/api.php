<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'angaza-shs'], function () {
    Route::group(['prefix' => 'angaza-credential'], function () {
        Route::get('/', 'AngazaCredentialController@show');
        Route::put('/', 'AngazaCredentialController@update');
    });
});
