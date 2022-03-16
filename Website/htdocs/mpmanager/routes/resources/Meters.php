<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 05.07.18
 * Time: 17:33
 */

/* Meter */
Route::group(['prefix' => 'meters', ], function () {
    Route::get('/', 'MeterController@index');
    Route::get('/{id}', 'MeterController@show');
    Route::post('/', 'MeterController@store');
    Route::put('/', 'MeterController@update');
    Route::get('/search', 'MeterController@Search');
    Route::delete('/{ownerId}', 'MeterController@destroy');
    Route::get('{serialNumber}/revenue', 'RevenueController@meterRevenue');


    Route::get('/{id}/all', 'MeterController@allRelations');


    Route::group(['prefix' => 'parameters'], function () {
        Route::get('/', 'MeterParameterController@index'); // list of all meters which are related to a customer
        Route::post('/', 'MeterParameterController@store');
        Route::get('/connection-types', 'MeterParameterController@connectionTypes');
    });

    Route::get('/parameters/{meterParameter}', 'MeterParameterController@show');


    Route::put('/{serialNumber}/parameters', 'MeterParameterController@update');


    Route::get('/{serialNumber}/transactions', 'MeterController@transactionList');
    Route::get('{serialNumber}/consumptions/{start}/{end}', 'MeterController@consumptionList');

    Route::get('/{migiGrid}/geoList', 'MeterController@meterGeoList');
});
/* Meter types */
Route::group(['prefix' => 'meter-types'], function () {
    Route::get('/', 'MeterTypeController@index');
    Route::get('/{id}', 'MeterTypeController@show');
    Route::post('/', 'MeterTypeController@store');
    Route::put('/{meterType}', 'MeterTypeController@update');
    Route::get('/{id}/list', 'MeterTypeController@meterList');
});
