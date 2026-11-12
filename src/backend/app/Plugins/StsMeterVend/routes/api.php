<?php

use App\Plugins\StsMeterVend\Http\Controllers\StsMeterVendController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'sts-meter-vend'], function () {
    Route::get('/status', [StsMeterVendController::class, 'status']);
    Route::get('/meters', [StsMeterVendController::class, 'meters']);
    Route::post('/keys/import', [StsMeterVendController::class, 'importKeys']);
    Route::get('/keys/template', [StsMeterVendController::class, 'keyTemplate']);
});
