<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ecreee-e-tender'], static function () {
    Route::group(['prefix' => 'ecreee-token', 'middleware' => 'jwt.verify'], static function () {
        Route::get('/', 'EcreeeTokenController@get');
        Route::post('/', 'EcreeeTokenController@store');
        Route::put('/{ecreeeTokenId}', 'EcreeeTokenController@update');
    });

    Route::group(['prefix' => 'ecreee-meter-data'], static function () {
        Route::get('/', 'EcreeeMeterDataController@index');
    });
});
