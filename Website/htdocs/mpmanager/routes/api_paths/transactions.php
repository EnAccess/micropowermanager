<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 14.09.18
 * Time: 11:37
 */


Route::get('transactions/', 'TransactionController@index');
Route::get('transactions/search', 'TransactionController@search');
Route::get('transactions/analytics/{period}', 'TransactionController@compare')->where('period', '[0-3]+');
Route::get('transactions/cancelled', 'TransactionController@cancelled');
Route::get('transactions/confirmed', 'TransactionController@confirmed');
Route::get('transactions/{id}', 'TransactionController@show')->where('id', '[0-9]+');

Route::get('transaction-providers/', 'TransactionProviderController@index');


Route::get('transactions/advanced', 'TransactionController@searchAdvanced');

