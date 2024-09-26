<?php

use Illuminate\Support\Facades\Route;

/* Country */
Route::group(['prefix' => 'countries', 'middleware' => 'jwt.verify'], function () {
    Route::get('/', 'CountryController@index'); // list of all countries
    Route::post('/', 'CountryController@store'); // store new country
    Route::get('/{country}', 'CountryController@show'); // country detail
});
