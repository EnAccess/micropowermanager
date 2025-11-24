<?php

/**
 * |--------------------------------------------------------------------------
 * | Web Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register web routes for your application. These
 * | routes are loaded by the RouteServiceProvider within a group which
 * | contains the "web" middleware group. Now create something great!
 * |.
 */

use App\DTO\TransactionDataContainer;
use App\Events\TransactionSuccessfulEvent;
use App\Jobs\EnergyTransactionProcessor;
use App\Jobs\TokenProcessor;
use App\Models\Transaction\Transaction;
use Illuminate\Support\Facades\Route;

Route::group(
    ['prefix' => '/jobs', 'middleware' => 'auth'],
    static function () {
        Route::get('/token/{id}/{recreate?}', static function () {
            $id = request('id');
            $recreate = (bool) request('recreate');
            $companyId = auth('agent_api')->user()->company_id;
            dispatch(new TokenProcessor($companyId, TransactionDataContainer::initialize(Transaction::find($id)), $recreate, 1));
        })->where('id', '[0-9]+')->name('jobs.token');

        Route::get('energy/{id}', function () {
            $id = request('id');
            $transaction = Transaction::find($id);
            $companyId = auth('agent_api')->user()->company_id;
            dispatch(new EnergyTransactionProcessor($companyId, $transaction->id));
        });
    }
);

/*
 * the group in which events can be fired manually again.
 */
Route::group(['prefix' => '/events', 'middleware' => 'auth'], function () {
    /*
     * Confirms a transaction (again).
     */
    Route::get('transaction/confirm/{id}', function () {
        // get id from the request
        $id = request('id');
        // find the transaction
        $transaction = Transaction::find($id);

        // fire event which confirms the transaction
        event(new TransactionSuccessfulEvent($transaction));
    })->where('id', '[0-9]+');
});

Route::group(['middleware' => 'auth'], function () {
    // Route::get('/user-data', [AdminController::class, 'auth']);
});
