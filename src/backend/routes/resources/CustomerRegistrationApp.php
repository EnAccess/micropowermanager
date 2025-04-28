<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\ConnectionGroupController;
use App\Http\Controllers\ConnectionTypeController;
use App\Http\Controllers\CustomerRegistrationAppController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\MeterTariffController;
use App\Http\Controllers\MeterTypeController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\SubConnectionTypeController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'customer-registration-app'], static function () {
    Route::get('/people', [PersonController::class, 'index']);
    Route::get('/manufacturers', [ManufacturerController::class, 'index']);
    Route::get('/meter-types', [MeterTypeController::class, 'index']);
    Route::get('/tariffs', [MeterTariffController::class, 'index']);
    Route::get('/cities', [CityController::class, 'index']);
    Route::get('/connection-groups', [ConnectionGroupController::class, 'index']);
    Route::get('/connection-types', [ConnectionTypeController::class, 'index']);
    Route::get('/sub-connection-types', [SubConnectionTypeController::class, 'index']);
    Route::post('/', [CustomerRegistrationAppController::class, 'store']);
});
