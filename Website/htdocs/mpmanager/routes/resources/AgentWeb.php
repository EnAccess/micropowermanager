<?php
// Web panel routes for agent
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['api', 'jwt.verify'],
    'prefix' => 'agents'

], static function ($router) {
    Route::get('/', 'AgentWebController@index');
    Route::get('/{agentId}', 'AgentWebController@show')->where('agentId', '[0-9]+');
    Route::post('/', 'AgentWebController@store');
    Route::put('/{agentId}', 'AgentWebController@update');
    Route::get('/search', 'AgentWebController@search');
    Route::post('/reset-password', 'AgentWebController@resetPassword');
    Route::delete('/{agentId}', 'AgentWebController@destroy');

    Route::group(['prefix' => 'assigned'], function () {
        Route::post('/', 'AgentAssignedApplianceWebController@store');
        Route::get('/{agentId}', 'AgentAssignedApplianceWebController@index');
    });
    Route::group(['prefix' => 'sold'], function () {
        Route::get('/{agentId}', 'AgentSoldApplianceWebController@index');
    });
    Route::group(['prefix' => 'commissions'], function () {

        Route::get('/', 'AgentCommissionWebController@index');
        Route::post('/', 'AgentCommissionWebController@store');
        Route::delete('/{agentCommissionId}', 'AgentCommissionWebController@destroy');
        Route::put('/{agentCommissionId}', 'AgentCommissionWebController@update');
    });
    Route::group(['prefix' => 'receipt'], function () {
        Route::get('/', 'AgentReceiptWebController@index');
        Route::get('/{agentId}', 'AgentReceiptWebController@show');
        Route::post('/', 'AgentReceiptWebController@store');
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
        Route::post('/', 'AgentChargeWebController@store');
    });

});
