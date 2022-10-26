<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 05.07.18
 * Time: 17:30
 */

use Illuminate\Support\Facades\Route;
/* City*/
Route::group(['prefix' => 'cities' , 'middleware' => 'jwt.verify'], function () {
    Route::get('/', 'CityController@index');
    Route::get('/{cityId}', 'CityController@show')->where('id', '[0-9]+');
    Route::post('/', 'CityController@store');
    Route::put('/{cityId}', 'CityController@update');
});
