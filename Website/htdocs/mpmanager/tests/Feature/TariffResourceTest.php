<?php


namespace Tests\Feature;


use App\Jobs\TariffPricingComponentsCalculator;
use App\Models\Meter\MeterTariff;
use App\Models\SocialTariff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TariffResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_basic_tariff(): void
    {
        $headers = $this->headers();
        $request = $this->post('/api/tariffs', [
            'name' => 'Tariff',
            'price' => 10000,
            'currency' => 'TRY',
            'factor' => 1,
        ],
            $headers
        );

        $request->assertStatus(201);
        $this->assertCount(1, MeterTariff::all());
        $this->assertEquals(10000, MeterTariff::first()->total_price);
    }


    public function test_create_tariff_with_price_components(): void
    {
        Queue::fake();
        $this->withoutExceptionHandling();
        $headers = $this->headers();
        $request = $this->post('/api/tariffs', [
            'name' => 'Tariff',
            'price' => 10000,
            'currency' => 'TRY',
            'factor' => 1,
            'components' => [
                [
                    'name' => 'Cost-1',
                    'price' => 10000,
                ]
            ],
        ],
            $headers
        );

        $request->assertStatus(201);
        $this->assertCount(1, MeterTariff::all());

        Queue::assertPushed(TariffPricingComponentsCalculator::class);
    }

    public function test_create_tariff_with_social_inputs(): void
    {
        $this->withoutExceptionHandling();
        $headers = $this->headers();
        $request = $this->post('/api/tariffs', [
            'name' => 'Tariff',
            'price' => 10000,
            'currency' => 'TRY',
            'factor' => 1,
            'social_tariff' => [
                'daily_allowance' => 10,
                'price' => 100000,
                'initial_energy_budget' => 4,
                'maximum_stacked_energy' => 34,
            ],
        ],
            $headers
        );
        $request->assertStatus(201);
        $this->assertCount(1, MeterTariff::all());
    }
}
