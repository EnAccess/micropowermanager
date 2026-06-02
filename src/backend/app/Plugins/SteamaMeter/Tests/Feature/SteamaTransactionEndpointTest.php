<?php

namespace App\Plugins\SteamaMeter\Tests\Feature;

use App\Plugins\SteamaMeter\Models\SteamaCustomer;
use App\Plugins\SteamaMeter\Models\SteamaTransaction;
use Database\Factories\UserFactory;
use Tests\TestCase;

class SteamaTransactionEndpointTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        $this->actingAs(UserFactory::new()->create());
    }

    public function testReturnsOnlyTransactionsForTheGivenCustomer(): void {
        $customer = SteamaCustomer::query()->create([
            'site_id' => 1,
            'user_type_id' => 1,
            'customer_id' => 555,
            'mpm_customer_id' => 555,
        ]);

        $this->createTransaction(transactionId: 1001, customerId: 555);
        $this->createTransaction(transactionId: 1002, customerId: 555);
        $this->createTransaction(transactionId: 1003, customerId: 999);

        $response = $this->getJson("/api/steama-meters/steama-transaction/{$customer->id}");

        $response->assertOk()
            ->assertJsonFragment(['transaction_id' => 1001])
            ->assertJsonFragment(['transaction_id' => 1002])
            ->assertJsonMissing(['transaction_id' => 1003]);
    }

    private function createTransaction(int $transactionId, int $customerId): void {
        SteamaTransaction::query()->create([
            'transaction_id' => $transactionId,
            'site_id' => 1,
            'customer_id' => $customerId,
            'amount' => 10,
            'category' => 'PAY',
            'provider' => 'AP',
            'timestamp' => now(),
            'synchronization_status' => 'synchronized',
        ]);
    }
}
