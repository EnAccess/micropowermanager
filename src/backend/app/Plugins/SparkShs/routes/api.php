<?php

use App\Plugins\SparkShs\Http\Controllers\SparkShsCredentialController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'spark-shs'], function () {
    Route::group(['prefix' => 'credentials'], function () {
        Route::get('/', [SparkShsCredentialController::class, 'show']);
        Route::put('/', [SparkShsCredentialController::class, 'update']);
        Route::post('/check', [SparkShsCredentialController::class, 'check']);
    });
});
