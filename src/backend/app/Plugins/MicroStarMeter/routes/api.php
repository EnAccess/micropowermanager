<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'micro-star-meters'], function () {
    Route::group(['prefix' => 'micro-star-credential'], function () {
        Route::get('/', 'MicroStarCredentialController@show');
        Route::put('/', 'MicroStarCredentialController@update');
    });
    Route::group(['prefix' => 'micro-star-cert'], function () {
        Route::get('/', 'MicroStarCertificateController@show');
        Route::post('/', 'MicroStarCertificateController@store');
    });
    Route::get('/test', 'MicroStarTestController@show');
});
