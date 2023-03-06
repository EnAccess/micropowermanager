<?php
use Illuminate\Support\Facades\Route;
Route::group(['prefix' => 'micro-star-meters'], function () {
    Route::group(['prefix' => 'micro-star-credential'], function () {
        Route::get('/', 'MicroStarCredentialController@show');
        Route::put('/', 'MicroStarCredentialController@update');
    });
    Route::get('/test', 'MicroStarTestController@show');
});