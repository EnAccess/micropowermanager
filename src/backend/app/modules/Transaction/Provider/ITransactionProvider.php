<?php

namespace MPM\Transaction\Provider;

use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;

interface ITransactionProvider {
    // saves the main transaction
    public function saveTransaction(): void;

    // accepts the payment or cancel the payment
    public function sendResult(bool $requestType, Transaction $transaction): void;

    // validates incoming request - accepts either Transaction or WaveComTransaction
    public function validateRequest(Transaction|WaveComTransaction $request): void;

    // the first contact, confirms that the request data is valid and could be processed
    public function confirm(): void;

    // user message from mobile provider
    public function getMessage(): string;

    // sent amount
    public function getAmount(): int;

    // sender
    public function getSender(): string;

    public function saveCommonData(): Model;

    // init method accepts either Transaction or WaveComTransaction
    public function init(mixed $transaction): void;

    public function addConflict(?string $message): void;

    public function getTransaction(): Transaction;
}
