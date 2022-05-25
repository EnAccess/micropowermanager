<?php
// Android App Services
Route::group([
    'middleware' => ['agent_api',],
    'prefix' => 'app'
], function () {

    Route::post('login', 'AgentAuthController@login');
    Route::post('logout', 'AgentAuthController@logout');
    Route::post('refresh', 'AgentAuthController@refresh');
    Route::get('me', 'AgentAuthController@me');
    Route::group(['prefix' => 'agents', 'middleware' => ['jwt.verify:agent'],], function () {
        Route::post('/firebase', 'AgentFirebaseController@update');
        Route::get('/balance', 'AgentBalanceController@show');
        Route::group(['prefix' => 'customers'], function () {
            Route::get('/', 'AgentCustomerController@index');
            Route::get('/search', 'AgentCustomerController@search');
            Route::get('/{customerId}/graph/{period}/{limit?}/{order?}',
                'AgentCustomersPaymentHistoryController@show')->where('customerId', '[0-9]+');
            Route::get('/graph/{period}/{limit?}/{order?}',
                'AgentCustomersPaymentHistoryController@index');
        });
        Route::group(['prefix' => 'transactions'], function () {
            Route::get('/', 'AgentTransactionsController@index');
            Route::get('/{customerId}', 'AgentTransactionsController@show');

        });
        Route::group(['prefix' => 'appliances'], function () {

            Route::get('/', 'AgentSoldApplianceController@index');
            Route::get('/{customerId}', 'AgentSoldApplianceController@show');
            Route::post('/', [
                'middleware' => 'agent.balance',
                'uses' => 'AgentSoldApplianceController@store'
            ])->name('agent-sell-appliance');
        });
        Route::group(['prefix' => 'applianceTypes'], function () {
            Route::get('/', 'AgentAssignedAppliancesController@index');
        });
        Route::group(['prefix' => 'ticket'], function () {
            Route::get('/', 'AgentTicketController@index');
            Route::get('/{ticketId}', 'AgentTicketController@show');
            Route::get('/customer/{customerId}', 'AgentCustomerTicketController@show');
            Route::post('/', 'AgentTicketController@store');
        });
        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('/boxes',  'AgentDashboardBoxesController@show');
            Route::get('/graph',  'AgentDashboardBalanceHistoryController@show');
            Route::get('/revenue','AgentDashboardRevenueController@show');

        });
    });

});
