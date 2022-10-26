<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'energy', 'middleware' => 'jwt.verify'], function(){
    Route::post('/', 'EnergyController@store');
    Route::get('/{id}', 'EnergyController@show');
});

