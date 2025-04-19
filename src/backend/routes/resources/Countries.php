<?php

use App\Http\Controllers\CountryController;
use Illuminate\Support\Facades\Route;

/* Country */
Route::middleware('jwt.verify')
    ->prefix('countries')
    ->group(function () {
        Route::get('/', [CountryController::class, 'index']); // list of all countries
        Route::post('/', [CountryController::class, 'store']); // store new country
        Route::get('/{country}', [CountryController::class, 'show']); // country detail
    });
