<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\ConnectionGroupController;
use App\Http\Controllers\ConnectionTypeController;
use App\Http\Controllers\CustomerRegistrationAppController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\MeterTypeController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\SubConnectionTypeController;
use App\Http\Controllers\TariffController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'customer-registration-app'], static function () {
    Route::get('/people', [PersonController::class, 'indexForCustomerRegistrationApp']);
    Route::get('/manufacturers', [ManufacturerController::class, 'indexForCustomerRegistrationApp']);
    Route::get('/meter-types', [MeterTypeController::class, 'indexForCustomerRegistrationApp']);
    Route::get('/tariffs', [TariffController::class, 'indexForCustomerRegistrationApp']);
    Route::get('/cities', [CityController::class, 'indexForCustomerRegistrationApp']);
    Route::get('/connection-groups', [ConnectionGroupController::class, 'indexForCustomerRegistrationApp']);
    Route::get('/connection-types', [ConnectionTypeController::class, 'indexForCustomerRegistrationApp']);
    Route::get('/sub-connection-types', [SubConnectionTypeController::class, 'indexForCustomerRegistrationApp']);
    Route::post('/', [CustomerRegistrationAppController::class, 'store']);
});
