<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'energy', 'middleware' => 'jwt.verify'], function () {
    Route::get('/{id}', 'EnergyController@show');
});
