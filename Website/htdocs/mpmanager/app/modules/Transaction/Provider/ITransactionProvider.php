<?php
namespace MPM\Transaction\Provider;

use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Model;

interface ITransactionProvider
{
    //saves the main transaction
    public function saveTransaction();

    //accepts the payment or cancel the payment
    public function sendResult(bool $requestType, Transaction $transaction);

    //validates incoming request
    public function validateRequest($request);

    //the first contact, confirms that the request data is valid and could be processed
    public function confirm(): void;

    //user message from mobile provider
    public function getMessage(): string;

    //sent amount
    public function getAmount(): int;

    //sender
    public function getSender(): string;

    public function saveCommonData(): Model;

    public function init($transaction): void;

    public function addConflict(?string $message): void;

    public function getTransaction(): Transaction;
}
