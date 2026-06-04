<?php

use App\Plugins\SteamaMeter\Http\Controllers\SteamaAgentController;
use App\Plugins\SteamaMeter\Http\Controllers\SteamaCredentialController;
use App\Plugins\SteamaMeter\Http\Controllers\SteamaCustomerController;
use App\Plugins\SteamaMeter\Http\Controllers\SteamaMeterController;
use App\Plugins\SteamaMeter\Http\Controllers\SteamaSettingController;
use App\Plugins\SteamaMeter\Http\Controllers\SteamaSiteController;
use App\Plugins\SteamaMeter\Http\Controllers\SteamaSmsBodyController;
use App\Plugins\SteamaMeter\Http\Controllers\SteamaSmsFeedbackController;
use App\Plugins\SteamaMeter\Http\Controllers\SteamaSmsSettingController;
use App\Plugins\SteamaMeter\Http\Controllers\SteamaSmsVariableDefaultValueController;
use App\Plugins\SteamaMeter\Http\Controllers\SteamaSyncSettingController;
use App\Plugins\SteamaMeter\Http\Controllers\SteamaTransactionController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'steama-meters'], function () {
    Route::group(['prefix' => 'steama-credential'], function () {
        Route::get('/', [SteamaCredentialController::class, 'show']);
        Route::put('/', [SteamaCredentialController::class, 'update']);
    });
    Route::group(['prefix' => 'steama-site'], function () {
        Route::get('/', [SteamaSiteController::class, 'index']);
        Route::get('/sync', [SteamaSiteController::class, 'sync']);
        Route::get('/sync-check', [SteamaSiteController::class, 'checkSync']);
        Route::get('/count', [SteamaSiteController::class, 'count']);
        Route::get('/location', [SteamaSiteController::class, 'location']);
    });
    Route::group(['prefix' => 'steama-customer'], function () {
        Route::get('/', [SteamaCustomerController::class, 'index']);
        Route::get('/sync', [SteamaCustomerController::class, 'sync']);
        Route::get('/sync-check', [SteamaCustomerController::class, 'checkSync']);
        Route::get('/count', [SteamaCustomerController::class, 'count']);
        Route::put('/{customer}', [SteamaCustomerController::class, 'update']);
        Route::get('/{customerId}', [SteamaCustomerController::class, 'get']);
        Route::get('/advanced/search', [SteamaCustomerController::class, 'search']);
    });
    Route::group(['prefix' => 'steama-meter'], function () {
        Route::get('/', [SteamaMeterController::class, 'index']);
        Route::get('/sync', [SteamaMeterController::class, 'sync']);
        Route::get('/sync-check', [SteamaMeterController::class, 'checkSync']);
        Route::get('/count', [SteamaMeterController::class, 'count']);
    });
    Route::group(['prefix' => 'steama-agent'], function () {
        Route::get('/', [SteamaAgentController::class, 'index']);
        Route::get('/sync', [SteamaAgentController::class, 'sync']);
        Route::get('/sync-check', [SteamaAgentController::class, 'checkSync']);
        Route::get('/count', [SteamaAgentController::class, 'count']);
    });
    Route::group(['prefix' => 'steama-transaction'], function () {
        Route::get('/', [SteamaTransactionController::class, 'index']);
        Route::get('/sync', [SteamaTransactionController::class, 'sync']);
        Route::get('/{customer}', [SteamaTransactionController::class, 'getByCustomer']);
    });
    Route::group(['prefix' => 'steama-setting'], function () {
        Route::get('/', [SteamaSettingController::class, 'index']);
        Route::group(['prefix' => 'sms-setting'], function () {
            Route::put('/', [SteamaSmsSettingController::class, 'update']);
            // Sms
            Route::group(['prefix' => 'sms-body'], static function () {
                Route::get('/', [SteamaSmsBodyController::class, 'index']);
                Route::put('/', [SteamaSmsBodyController::class, 'update']);
            });
            Route::group(['prefix' => 'sms-variable-default-value'], static function () {
                Route::get('/', [SteamaSmsVariableDefaultValueController::class, 'index']);
            });
        });
        Route::group(['prefix' => 'sync-setting'], function () {
            Route::put('/', [SteamaSyncSettingController::class, 'update']);
        });
        Route::group(['prefix' => 'feedback-word'], function () {
            Route::get('/', [SteamaSmsFeedbackController::class, 'index']);
            Route::put('/{smsFeedbackWord}', [SteamaSmsFeedbackController::class, 'update']);
        });
    });
});
