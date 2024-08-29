<?php

/* Ticketing package prefix is ticket */

Route::group(['prefix' => 'ticket'], function () {
    Route::get('/users/', 'ManufacturerController@index');
});

Route::group(['prefix' => 'tickets'], function () {
    Route::post('/export/outsource', 'ExportController@outsource');
});
