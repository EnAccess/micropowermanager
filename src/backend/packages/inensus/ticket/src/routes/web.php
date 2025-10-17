<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api'], function () {
    Route::group(['prefix' => 'ticket'], function () {
        Route::get('/', 'TicketController@index')->middleware('permission:tickets.view');
        Route::post('/', 'TicketCustomerController@store')->middleware('permission:tickets.create');
        Route::delete('/{ticketId}', 'TicketController@destroy')->middleware('permission:tickets.delete');
        Route::get('/{id}', 'TicketController@show')->middleware('permission:tickets.view');
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', 'TicketUserController@index');
        Route::post('/external', 'TicketUserController@storeExternal');
    });
    Route::group(['prefix' => 'agents'], function () {
        Route::get('/{agentId}', 'TicketAgentController@index');
    });
    Route::group(['prefix' => 'labels'], function () {
        Route::get('/', 'TicketCategoryController@index')->middleware('permission:tickets.view');
        Route::post('/', 'TicketCategoryController@store')->middleware('permission:tickets.create');
    });
    Route::get('/tickets/user/{id}', 'TicketCustomerController@index');
    Route::post('tickets/comments', 'TicketCommentController@store')->middleware('permission:tickets.update');

    Route::get('/export', 'TicketExportController@index')->middleware('permission:tickets.export');
    Route::post('/export/outsource', 'TicketExportController@outsource')->middleware('permission:tickets.export');
    Route::get('/export/download/{id}/book-keeping', 'TicketExportController@download')->middleware('permission:tickets.export');
});
