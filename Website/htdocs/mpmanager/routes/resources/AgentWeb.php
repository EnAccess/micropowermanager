<?php
// Web panel routes for agent
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['api', 'jwt.verify'],
    'prefix' => 'agents'

], static function ($router) {
    Route::get('/', 'AgentController@index');
    Route::get('/{agentId}', 'AgentController@show')->where('agentId', '[0-9]+');
    Route::post('/', 'AgentController@store');
    Route::put('/{agentId}', 'AgentController@update');
    Route::get('/search', 'AgentController@search');
    Route::post('/reset-password', 'AgentController@resetPassword');
    Route::delete('/{agentId}', 'AgentController@destroy');

    Route::group(['prefix' => 'assigned'], function () {
        Route::post('/', 'AgentAssignedApplianceWebController@store');
        Route::get('/{agentId}', 'AgentAssignedApplianceWebController@index');
    });
    Route::group(['prefix' => 'sold'], function () {
        Route::get('/{agentId}', 'AgentSoldApplianceWebController@index');
    });
    Route::group(['prefix' => 'commissions'], function () {

        Route::get('/', 'AgentCommissionController@index');
        Route::post('/', 'AgentCommissionController@store');
        Route::delete('/{agentCommissionId}', 'AgentCommissionController@destroy');
        Route::put('/{agentCommissionId}', 'AgentCommissionController@update');
    });
    Route::group(['prefix' => 'receipt'], function () {
        Route::get('/', 'AgentReceiptController@index');
        Route::get('/{agentId}', 'AgentReceiptController@show');
        Route::post('/', 'AgentReceiptController@store');
    });
    Route::group(['prefix' => 'transactions'], function () {
        Route::get('/{agentId}', 'AgentTransactionWebController@index')->where('agentId', '[0-9]+');

    });
    Route::group(['prefix' => 'balance'], function () {
        Route::group(['prefix' => 'history'], function () {
            Route::get('/{agentId}', 'AgentBalanceHistoryWebController@index');
        });
    });
    Route::group(['prefix' => 'charge'], function () {
        Route::post('/', 'AgentChargeController@store');
    });

});
