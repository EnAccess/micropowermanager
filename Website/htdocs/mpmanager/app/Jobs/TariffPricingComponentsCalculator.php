<?php

namespace App\Jobs;

use App\Models\Meter\MeterTariff;
use App\Models\TariffPricingComponent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TariffPricingComponentsCalculator implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var MeterTariff
     */
    private $tariff;
    private $components;

    /**
     * Create a new job instance.
     *
     * @param MeterTariff $tariff
     * @param $components
     */
    public function __construct(MeterTariff $tariff, $components)
    {
        $this->tariff = $tariff;
        $this->components = $components;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $totalPrice = $this->tariff->total_price;
        foreach ($this->components as $component) {
            $totalPrice += $component['price'];
            $this->tariff->pricingComponent()->save(
                TariffPricingComponent::make(
                    [
                    'name' => $component['name'],
                    'price' => $component['price'],
                    ]
                )
            );
        }
        $this->tariff->update(['total_price' => $totalPrice]);
    }
}
