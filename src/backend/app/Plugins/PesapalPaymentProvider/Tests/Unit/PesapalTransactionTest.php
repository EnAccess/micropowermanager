<?php

namespace App\Plugins\PesapalPaymentProvider\Tests\Unit;

use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use PHPUnit\Framework\TestCase as BaseTestCase;

class PesapalTransactionTest extends BaseTestCase {
    public function testReturnsCorrectTransactionName(): void {
        $this->assertEquals('pesapal_transaction', PesapalTransaction::getTransactionName());
    }

    public function testHasCorrectStatusConstants(): void {
        $this->assertEquals(0, PesapalTransaction::STATUS_REQUESTED);
        $this->assertEquals(1, PesapalTransaction::STATUS_SUCCESS);
        $this->assertEquals(2, PesapalTransaction::STATUS_COMPLETED);
        $this->assertEquals(-1, PesapalTransaction::STATUS_FAILED);
        $this->assertEquals(3, PesapalTransaction::STATUS_ABANDONED);
        $this->assertEquals(5, PesapalTransaction::MAX_ATTEMPTS);
    }

    public function testHasCorrectRelationName(): void {
        $this->assertEquals('pesapal_transaction', PesapalTransaction::RELATION_NAME);
    }
}
