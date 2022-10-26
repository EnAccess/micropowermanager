<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 05.07.18
 * Time: 17:33
 */

use Illuminate\Support\Facades\Route;
/* Address */
Route::group(['prefix' => 'addresses', 'middleware' => 'jwt.verify'], function () {
    Route::get('/', 'AddressController@index');
    Route::get('/{id}', 'AddressController@show');
    Route::post('/', 'AddressController@store');
    Route::put('/{id}', 'AddressController@update');
});
