<?php

use Illuminate\Support\Facades\Route;

/* Meter */
Route::group(['prefix' => 'meters'], function () {
    Route::get('/', 'MeterController@index');
    Route::post('/', 'MeterController@store');
    Route::put('/{meter}', 'MeterController@update');
    Route::get('/search', 'MeterController@search');
    Route::get('/{serialNumber}', 'MeterController@show');
    Route::delete('/{meterId}', 'MeterController@destroy');
    Route::get('/{meterId}/all', 'MeterController@allRelations');
    Route::put('/', 'MeterGeographicalInformationController@update');
    Route::put('/{serialNumber}/parameters', 'MeterParameterController@update');
    Route::get('/{serialNumber}/transactions', 'MeterPaymentHistoryController@show');
    Route::get('/{serialNumber}/consumptions/{start}/{end}', 'MeterConsumptionController@show');
    Route::get('/{serialNumber}/revenue', 'MeterRevenueController@show');
    Route::get('/{miniGrid}/geoList', 'MeterGeographicalInformationController@index');
});

/* Meter types */
Route::group(['prefix' => 'meter-types'], function () {
    Route::get('/', 'MeterTypeController@index');
    Route::get('/{meterTypeId}', 'MeterTypeController@show');
    Route::post('/', 'MeterTypeController@store');
    Route::put('/{meterTypeId}', 'MeterTypeController@update');
    Route::get('/{meterTypeId}/list', 'MeterTypeMeterController@show');
});
