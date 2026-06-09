<?php

namespace App\Plugins\SteamaMeter\Tests\Unit;

use App\Plugins\SteamaMeter\Models\SteamaTransaction;
use Carbon\Carbon;
use Tests\TestCase;

class SteamaTransactionTest extends TestCase {
    public function testTimestampAcceptsTheIso8601ValueReturnedBySteama(): void {
        $transaction = SteamaTransaction::query()->create([
            'transaction_id' => 449690886,
            'site_id' => 27604,
            'customer_id' => 1574212,
            'amount' => 50.00,
            'category' => 'UCU',
            'provider' => 'AP',
            'timestamp' => '2024-12-19T10:33:09Z',
            'synchronization_status' => 'N/A',
        ])->fresh();

        $this->assertInstanceOf(Carbon::class, $transaction->timestamp);
        $this->assertEquals('2024-12-19 10:33:09', $transaction->timestamp->toDateTimeString());
    }

    public function testAmountAcceptsValuesAtAndAboveOneMillion(): void {
        $transaction = SteamaTransaction::query()->create([
            'transaction_id' => 502182757,
            'site_id' => 27604,
            'customer_id' => 1574225,
            'amount' => 1000000.00,
            'category' => 'UCU',
            'provider' => 'AP',
            'timestamp' => '2025-08-25T11:31:00Z',
            'synchronization_status' => 'N/A',
        ])->fresh();

        $this->assertEquals(1000000.00, (float) $transaction->amount);
    }
}
