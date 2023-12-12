<?php

namespace App\Http\Controllers;

use App\Models\Transaction\VodacomTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VodacomTransactionController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return void
     */
    public function store(Request $request): void
    {
        //get Transaction object
        $transactionData = request('transaction')->transaction;

        /** @var VodacomTransaction $vodacomTransaction */
        $vodacomTransaction = VodacomTransaction::query()->create([
            'conversation_id' => $transactionData->conversationID,
            'originator_conversation_id' => $transactionData->originatorConversationID,
            'mpesa_receipt' => $transactionData->mpesaReceipt,
            'transaction_date' => $transactionData->transactionDate,
            'transaction_id' => $transactionData->transactionID,
        ]);

        $vodacomTransaction->transaction()->create([
            'amount' => $transactionData->amount,
            'sender' => $transactionData->initiator,
            'message' => $transactionData->accountReference,
        ]);
    }
}
