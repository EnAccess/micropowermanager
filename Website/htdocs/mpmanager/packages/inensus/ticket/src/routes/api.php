<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 28.08.18
 * Time: 13:19
 */

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


/* Ticketing package prefix is ticket */

Route::group(['prefix' => 'ticket'], function () {
    Route::get('/users/', 'ManufacturerController@index');

});


Route::group(['prefix' => 'tickets'], function () {
    Route::post('/export/outsource', 'ExportController@outsource');
});
