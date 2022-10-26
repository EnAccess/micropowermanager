<?php

namespace App\Jobs;

use App\Models\Meter\MeterTariff;
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
    private $tariffPricingComponentService;
    /**
     * Create a new job instance.
     *
     * @param MeterTariff $tariff
     * @param $components
     */
    public function __construct(MeterTariff $tariff, $components, $tariffPricingComponentService)
    {

        $this->tariff = $tariff;
        $this->components = $components;
        $this->tariffPricingComponentService  = $tariffPricingComponentService;
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
            $tariffPricingComponentData = [
                'name' => $component['name'],
                'price' => $component['price'],
            ];
            $tariffPricingComponent = $this->tariffPricingComponentService->make($tariffPricingComponentData);
            $this->tariff->pricingComponent()->save($tariffPricingComponent);
        }

        $this->tariff->update(['total_price' => $totalPrice]);
    }
}
