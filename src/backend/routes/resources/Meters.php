<?php

use App\Http\Controllers\MeterConsumptionController;
use App\Http\Controllers\MeterController;
use App\Http\Controllers\MeterGeographicalInformationController;
use App\Http\Controllers\MeterPaymentHistoryController;
use App\Http\Controllers\MeterRevenueController;
use App\Http\Controllers\MeterTypeController;
use App\Http\Controllers\MeterTypeMeterController;
use Illuminate\Support\Facades\Route;

/* Meter */
Route::group(['prefix' => 'meters'], function () {
    Route::get('/', [MeterController::class, 'index']);
    Route::post('/', [MeterController::class, 'store']);
    Route::get('/connection-types', [MeterController::class, 'showConnectionTypes']);
    Route::put('/{meter}', [MeterController::class, 'update']);
    Route::get('/search', [MeterController::class, 'search']);
    Route::get('/{serialNumber}', [MeterController::class, 'show']);
    Route::delete('/{meterId}', [MeterController::class, 'destroy']);
    Route::get('/{meterId}/all', [MeterController::class, 'allRelations']);
    Route::put('/', [MeterGeographicalInformationController::class, 'update']);
    Route::get('/{serialNumber}/transactions', [MeterPaymentHistoryController::class, 'show']);
    Route::get('/{serialNumber}/consumptions/{start}/{end}', [MeterConsumptionController::class, 'show']);
    Route::get('/{serialNumber}/revenue', [MeterRevenueController::class, 'show']);
    Route::get('/{miniGrid}/geoList', [MeterGeographicalInformationController::class, 'index']);
});

/* Meter types */
Route::group(['prefix' => 'meter-types'], function () {
    Route::get('/', [MeterTypeController::class, 'index']);
    Route::get('/{meterTypeId}', [MeterTypeController::class, 'show']);
    Route::post('/', [MeterTypeController::class, 'store']);
    Route::put('/{meterTypeId}', [MeterTypeController::class, 'update']);
    Route::get('/{meterTypeId}/list', [MeterTypeMeterController::class, 'show']);
});
