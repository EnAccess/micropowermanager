<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 28.08.18
 * Time: 13:19.
 */

/* Ticketing package prefix is ticket */

Route::group(['prefix' => 'ticket'], function () {
    Route::get('/users/', 'ManufacturerController@index');
});

Route::group(['prefix' => 'tickets'], function () {
    Route::post('/export/outsource', 'ExportController@outsource');
});
