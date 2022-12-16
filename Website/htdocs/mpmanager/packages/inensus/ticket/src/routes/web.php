<?php

use Illuminate\Support\Facades\Route;


Route::get('/{trelloId}', 'TicketController@show');

Route::group(['prefix' => 'api'], function () {
    Route::get('/', 'TicketController@index');
    Route::post('/ticket', 'TicketCustomerController@store');
    Route::delete('/ticket/{ticketId}', 'TicketController@destroy');

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', 'TicketUserController@index');
        Route::post('/external', 'TicketUserController@storeExternal');
    });
    Route::group(['prefix' => 'agents'], function () {
        Route::get('/{agentId}', 'TicketAgentController@index');
    });
    Route::group(['prefix' => 'labels'], function () {
        Route::get('/', 'TicketCategoryController@index');
        Route::post('/', 'TicketCategoryController@store');
    });
    Route::get('/tickets/user/{id}', 'TicketCustomerController@index');
    Route::post('tickets/comments', 'TicketCommentController@store');

    Route::get('/export', 'TicketExportController@index');
    Route::post('/export/outsource', 'TicketExportController@outsource');
    Route::get('/export/download/{id}/book-keeping', 'TicketExportController@download');
});

