<?php

namespace App\Plugins\FlutterwavePaymentProvider\Tests\Unit;

use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveTransaction;
use PHPUnit\Framework\TestCase as BaseTestCase;

class FlutterwaveTransactionTest extends BaseTestCase {
    public function testReturnsCorrectTransactionName(): void {
        $this->assertEquals('flutterwave_transaction', FlutterwaveTransaction::getTransactionName());
    }

    public function testHasCorrectStatusConstants(): void {
        $this->assertEquals(0, FlutterwaveTransaction::STATUS_REQUESTED);
        $this->assertEquals(1, FlutterwaveTransaction::STATUS_SUCCESS);
        $this->assertEquals(2, FlutterwaveTransaction::STATUS_COMPLETED);
        $this->assertEquals(-1, FlutterwaveTransaction::STATUS_FAILED);
        $this->assertEquals(3, FlutterwaveTransaction::STATUS_ABANDONED);
        $this->assertEquals(5, FlutterwaveTransaction::MAX_ATTEMPTS);
    }

    public function testHasCorrectRelationName(): void {
        $this->assertEquals('flutterwave_transaction', FlutterwaveTransaction::RELATION_NAME);
    }
}
