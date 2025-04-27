<?php

use Illuminate\Support\Facades\Route;

/* Address */
Route::group(['prefix' => 'addresses', 'middleware' => 'jwt.verify'], function () {
    Route::get('/', 'AddressController@index');
    Route::get('/{id}', 'AddressController@show');
    Route::post('/', 'AddressController@store');
    Route::put('/{id}', 'AddressController@update');
});
