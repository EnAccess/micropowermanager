<?php

use Illuminate\Support\Facades\Route;

/* City */
Route::group(['prefix' => 'cities', 'middleware' => 'jwt.verify'], function () {
    Route::get('/', 'CityController@index');
    Route::get('/{cityId}', 'CityController@show')->where('id', '[0-9]+');
    Route::post('/', 'CityController@store');
    Route::put('/{cityId}', 'CityController@update');
});
