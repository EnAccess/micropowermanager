<?php

/* Meter */
Route::group(['prefix' => 'meters',], function () {
    Route::get('/', 'MeterController@index');
    Route::post('/', 'MeterController@store');
    Route::get('/search', 'MeterController@search');
    Route::get('/{serialNumber}', 'MeterController@show');
    Route::delete('/{ownerId}', 'MeterController@destroy');
    Route::get('/{meterId}/all', 'MeterController@allRelations');

    Route::put('/', 'MeterGeographicalInformationController@update');


    Route::group(['prefix' => 'parameters'], function () {
        Route::get('/', 'MeterParameterController@index'); // list of all meters which are related to a customer
        Route::post('/', 'MeterParameterController@store');
        Route::get('/connection-types', 'MeterParameterController@connectionTypes');
        Route::get('/{meterParameter}', 'MeterParameterController@show');
    });
    Route::put('/{serialNumber}/parameters', 'MeterParameterController@update');

    Route::get('/{serialNumber}/transactions', 'MeterPaymentHistoryController@show');

    Route::get('{serialNumber}/revenue', 'MeterRevenueController@show');

    Route::get('{serialNumber}/consumptions/{start}/{end}', 'MeterConsumptionController@show');

    Route::get('/{migiGrid}/geoList', 'MeterGeographicalInformationController@index');
});

/* Meter types */
Route::group(['prefix' => 'meter-types'], function () {
    Route::get('/', 'MeterTypeController@index');
    Route::get('/{id}', 'MeterTypeController@show');
    Route::post('/', 'MeterTypeController@store');
    Route::put('/{meterType}', 'MeterTypeController@update');
    Route::get('/{id}/list', 'MeterTypeController@meterList');
});
