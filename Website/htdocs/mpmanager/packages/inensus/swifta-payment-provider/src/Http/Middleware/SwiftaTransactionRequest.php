<?php


namespace Inensus\SwiftaPaymentProvider\Http\Middleware;

use App\Jobs\ProcessPayment;
use App\Models\Transaction\Transaction;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inensus\SwiftaPaymentProvider\Http\Exceptions\TransactionAmountDifferentException;
use Inensus\SwiftaPaymentProvider\Http\Exceptions\TransactionNotExistsException;

class SwiftaTransactionRequest
{
    private $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
    public function handle(Request $request, Closure $next)
    {
        try {
            $transaction= $this->checkTransactionIdExists($request);
            $request->attributes->add(['transaction' => $transaction]);
        } catch (\Exception $exception) {
            $response = collect([
                'success' => 0,
                'message' => $exception->getMessage()
            ]);
            return new Response($response, 400);
        }
        try {
            $amount = $request->input('amount');
            $this->checkAmountIsSame($amount,$transaction);
        } catch (\Exception $exception) {

            $response = collect([
                'success' => 0,
                'message' => $exception->getMessage()
            ]);
            return new Response($response, 400);
        }
        if (config('app.env') === 'production') {//production queue
            $queue = 'payment';
        } elseif (config('app.env') === 'staging') {
            $queue = 'staging_payment';
        } else { // local queue‚
            $queue = 'local_payment';
        }

        ProcessPayment::dispatch($transaction->id)->allOnConnection('redis')->onQueue($queue);
        return $next($request);
    }
    private function checkTransactionIdExists(Request $request)
    {
       $transactionId = $request->input('transaction_id');

        try {

           return  $this->transaction->newQuery()->findOrFail($transactionId);
        }catch (ModelNotFoundException $exception){
            throw  new \Exception('transaction_id validation field.');
        }

    }
    private function checkAmountIsSame($amount,$transaction)
    {

          if ($amount != (int)$transaction->amount){
              throw new \Exception('amount validation field.');
          }
    }
}