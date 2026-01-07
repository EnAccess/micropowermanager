<?php

namespace Inensus\PaystackPaymentProvider\Tests\Unit;

use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use PHPUnit\Framework\TestCase as BaseTestCase;

class PaystackTransactionTest extends BaseTestCase {
    /** @test */
    public function itReturnsCorrectTransactionName(): void {
        $this->assertEquals('paystack_transaction', PaystackTransaction::getTransactionName());
    }

    /** @test */
    public function itHasCorrectStatusConstants(): void {
        $this->assertEquals(0, PaystackTransaction::STATUS_REQUESTED);
        $this->assertEquals(1, PaystackTransaction::STATUS_SUCCESS);
        $this->assertEquals(2, PaystackTransaction::STATUS_COMPLETED);
        $this->assertEquals(-1, PaystackTransaction::STATUS_FAILED);
        $this->assertEquals(3, PaystackTransaction::STATUS_ABANDONED);
        $this->assertEquals(5, PaystackTransaction::MAX_ATTEMPTS);
    }

    /** @test */
    public function itHasCorrectRelationName(): void {
        $this->assertEquals('paystack_transaction', PaystackTransaction::RELATION_NAME);
    }
}
