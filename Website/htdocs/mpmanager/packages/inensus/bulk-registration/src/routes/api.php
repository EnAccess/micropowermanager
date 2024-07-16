<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'bulk-register'], function () {
    Route::group(['prefix' => 'import-csv'], function () {
        Route::post('/', 'ImportCsvController@store');
        Route::get('/download', 'ImportCsvController@download');
    });
});
