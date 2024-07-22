<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'customer-registration-app'], static function () {
    Route::get('/people', 'PersonController@index');
    Route::get('/manufacturers', 'ManufacturerController@index');
    Route::get('/meter-types', 'MeterTypeController@index');
    Route::get('/tariffs', 'MeterTariffController@index');
    Route::get('/cities', 'CityController@index');
    Route::get('/connection-groups', 'ConnectionGroupController@index');
    Route::get('/connection-types', 'ConnectionTypeController@index');
    Route::get('/sub-connection-types', 'SubConnectionTypeController@index');
    Route::post('/', 'CustomerRegistrationAppController@store');
});
