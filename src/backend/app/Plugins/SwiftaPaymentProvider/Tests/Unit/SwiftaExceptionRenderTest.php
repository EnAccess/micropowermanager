<?php

declare(strict_types=1);

namespace App\Plugins\SwiftaPaymentProvider\Tests\Unit;

use App\Plugins\SwiftaPaymentProvider\Exceptions\CipherNotValidException;
use App\Plugins\SwiftaPaymentProvider\Exceptions\TransactionAmountDifferentException;
use App\Plugins\SwiftaPaymentProvider\Exceptions\TransactionNotExistsException;
use App\Plugins\SwiftaPaymentProvider\Exceptions\TransactionNotSettleableException;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Swifta reads `success` to decide whether a call worked, so these pin the body shape and
 * status every Swifta endpoint answers an error with.
 */
class SwiftaExceptionRenderTest extends TestCase {
    public function testACallerFaultRendersTheSwiftaBodyShapeWithA400(): void {
        $response = new TransactionAmountDifferentException('amount validation field.')
            ->render(Request::create('/api/swifta/transaction', 'POST'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            ['success' => 0, 'message' => 'amount validation field.'],
            $response->getData(true)
        );
    }

    public function testEveryCallerFaultAnswersA400(): void {
        $request = Request::create('/api/swifta/transaction', 'POST');

        $this->assertSame(400, new CipherNotValidException('cipher validation field.')->render($request)->getStatusCode());
        $this->assertSame(400, new TransactionNotExistsException('transaction_id validation field.')->render($request)->getStatusCode());
    }

    /**
     * A transaction we cannot settle is our own data fault, so it answers a 5xx and Swifta
     * retries it rather than treating the callback as rejected.
     */
    public function testAnUnsettleableTransactionAnswersA500(): void {
        $response = new TransactionNotSettleableException('Transaction 7 has no Swifta transaction to settle.')
            ->render(Request::create('/api/swifta/transaction', 'POST'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(
            ['success' => 0, 'message' => 'Transaction 7 has no Swifta transaction to settle.'],
            $response->getData(true)
        );
    }
}
