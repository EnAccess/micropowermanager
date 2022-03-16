<?php


Route::group(['prefix' => 'energy', 'middleware' => 'jwt.verify'], function(){
    Route::post('/', 'EnergyController@store');
    Route::get('/{id}', 'EnergyController@show');
});

