<?php
// Web panel routes for agent
Route::group([
    'middleware' => ['api', 'jwt.verify'],
    'prefix' => 'agents'

], static function ($router) {
    Route::post('/', 'PersonController@store');
    Route::post('/', 'AgentController@store');
    Route::post('/reset-password', 'AgentController@resetPassword');
    Route::put('/{agent}', 'AgentController@update');
    Route::get('/', 'AgentController@index');
    Route::get('/{agent}', 'AgentController@show')->where('agent', '[0-9]+');
    Route::get('/search', 'AgentController@search');
    Route::delete('/{agent}', 'AgentController@destroy');
    Route::group(['prefix' => 'assigned'], function () {
        Route::post('/', 'AgentAssignedAppliancesController@store');
        Route::get('/{agent}', 'AgentAssignedAppliancesController@indexWeb');
    });
    Route::group(['prefix' => 'sold'], function () {
        Route::get('/{agent}', 'AgentSoldApplianceController@indexWeb');
    });
    Route::group(['prefix' => 'commissions'], function () {

        Route::get('/', 'AgentCommissionController@index');
        Route::post('/', 'AgentCommissionController@store');
        Route::delete('/{commission}', 'AgentCommissionController@destroy');
        Route::put('/{commission}', 'AgentCommissionController@update');
    });

    Route::group(['prefix' => 'receipt'], function () {
        Route::get('/', 'AgentReceiptController@listByUsers');
        Route::get('/{agent}', 'AgentReceiptController@index');
        Route::get('/listAll', 'AgentReceiptController@listAllReceipts');
        Route::post('/{agent}', 'AgentReceiptController@store');

    });
    Route::group(['prefix' => 'transactions'], function () {
        Route::get('/{agent}', 'AgentTransactionsController@indexWeb')->where('agent', '[0-9]+');

    });
    Route::group(['prefix' => 'balance'], function () {
        Route::group(['prefix' => 'history'], function () {
            Route::get('/{agent}', 'AgentBalanceHistoryController@indexWeb');
        });
    });
    Route::group(['prefix' => 'charge'], function () {
        Route::post('/{agent}', 'AgentChargeController@store');
    });

});
