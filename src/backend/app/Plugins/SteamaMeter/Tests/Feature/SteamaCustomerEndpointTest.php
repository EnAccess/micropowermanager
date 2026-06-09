<?php

namespace App\Plugins\SteamaMeter\Tests\Feature;

use App\Models\Person\Person;
use App\Plugins\SteamaMeter\Http\Clients\SteamaMeterApiClient;
use App\Plugins\SteamaMeter\Models\SteamaCustomer;
use Database\Factories\UserFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;

class SteamaCustomerEndpointTest extends TestCase {
    use MockeryPHPUnitIntegration;

    protected function setUp(): void {
        parent::setUp();
        $this->actingAs(UserFactory::new()->create());
    }

    public function testUpdateEndpointBindsCustomerByRouteAndPatchesSteama(): void {
        $person = Person::query()->create(['name' => 'Jane', 'surname' => 'Doe', 'is_customer' => 1]);
        $customer = SteamaCustomer::query()->create([
            'site_id' => 1,
            'user_type_id' => 1,
            'customer_id' => 4321,
            'mpm_customer_id' => $person->id,
            'energy_price' => 1,
            'account_balance' => 0,
            'low_balance_warning' => 0,
        ]);

        $this->mock(SteamaMeterApiClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('patch')
                ->once()
                ->withArgs(fn (string $url, array $data): bool => $url === '/customers/4321'
                    && $data['energy_price'] === 2.0
                    && $data['low_balance_warning'] === 15.0)
                ->andReturn($this->steamaCustomerApiPayload());
        });

        $response = $this->putJson("/api/steama-meters/steama-customer/{$customer->id}", [
            'id' => $customer->customer_id,
            'energy_price' => 2,
            'low_balance_warning' => 15,
        ]);

        $response->assertOk();
        $this->assertNotNull($customer->fresh()->hash);
    }

    /**
     * @return array<string, mixed>
     */
    private function steamaCustomerApiPayload(): array {
        return [
            'user_type' => 'CUSTOMER',
            'control_type' => 'AUTOMATIC',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'telephone' => '+255700000001',
            'site' => 1,
            'energy_price' => 2,
            'is_field_manager' => false,
            'payment_plan' => 'flat_rate',
            'TOU_hours' => '',
            'low_balance_warning' => 15,
            'account_balance' => 0,
        ];
    }
}
