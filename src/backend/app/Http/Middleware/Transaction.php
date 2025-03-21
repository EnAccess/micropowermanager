<?php

namespace App\Http\Middleware;

use App\Exceptions\PaymentProviderNotIdentified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MPM\Transaction\Provider\ITransactionProvider;

class Transaction {
    public function handle(Request $request, \Closure $next): mixed {
        try {
            $request->attributes->add(['transactionProcessor' => $this->determineSender($request)]);
        } catch (PaymentProviderNotIdentified $e) {
            return response()->json(['data' => ['message' => $e->getMessage()]], 401);
        }

        return $next($request);
    }

    private function determineSender(Request $request): ITransactionProvider {
        if (preg_match('/\/agent/', $request->url()) && auth('agent_api')->user()) {
            return resolve('AgentPaymentProvider');
        } else {
            Log::warning(
                'Unknown IP Sent Transaction',
                [
                    'id' => 43326782767462641456,
                    'message' => 'Payment identifier not in the white list',
                    'ip' => $request->ip(),
                    'data' => $request->getContent(),
                ]
            );

            throw new PaymentProviderNotIdentified('Payment identifier not in the white list', 43326782);
        }
    }
}
