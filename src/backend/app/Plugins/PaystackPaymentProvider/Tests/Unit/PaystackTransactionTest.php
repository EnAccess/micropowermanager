<?php

namespace App\Plugins\PaystackPaymentProvider\Tests\Unit;

use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use PHPUnit\Framework\TestCase as BaseTestCase;

class PaystackTransactionTest extends BaseTestCase {
    public function testReturnsCorrectTransactionName(): void {
        $this->assertEquals('paystack_transaction', PaystackTransaction::getTransactionName());
    }

    public function testHasCorrectStatusConstants(): void {
        $this->assertEquals(0, PaystackTransaction::STATUS_REQUESTED);
        $this->assertEquals(1, PaystackTransaction::STATUS_SUCCESS);
        $this->assertEquals(2, PaystackTransaction::STATUS_COMPLETED);
        $this->assertEquals(-1, PaystackTransaction::STATUS_FAILED);
        $this->assertEquals(3, PaystackTransaction::STATUS_ABANDONED);
        $this->assertEquals(5, PaystackTransaction::MAX_ATTEMPTS);
    }

    public function testHasCorrectRelationName(): void {
        $this->assertEquals('paystack_transaction', PaystackTransaction::RELATION_NAME);
    }
}
