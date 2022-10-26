<?php

namespace App\Services;

use App\Http\Requests\TariffCreateRequest;
use App\Jobs\TariffPricingComponentsCalculator;
use App\Models\AccessRate\AccessRate;
use App\Models\Meter\MeterParameter;
use App\Models\Meter\MeterTariff;
use App\Models\SocialTariff;
use App\Models\TariffPricingComponent;
use App\Models\TimeOfUsage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MeterTariffService implements IBaseService
{
    public function __construct(private MeterTariff $meterTariff)
    {
    }

    public function getById($meterTariffId)
    {
        return $this->meterTariff->newQuery()
            ->with(['accessRate', 'pricingComponent', 'socialTariff', 'tou'])
            ->findOrFail($meterTariffId);
    }

    public function create($meterTariffData)
    {
        return $this->meterTariff->newQuery()->create([
            'name' => $meterTariffData['name'],
            'factor' => $meterTariffData['factor'],
            'currency' => $meterTariffData['currency'],
            'price' => $meterTariffData['price'],
            'total_price' => $meterTariffData['price'],
        ]);
    }

    public function update($meterTariff, $meterTariffData)
    {
        $meterTariff->update(
            $meterTariffData
        );
        $meterTariff->fresh();

        return $meterTariff;
    }

    public function delete($meterTariff)
    {
        return  $meterTariff->delete();
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->meterTariff->newQuery()
                ->with(['accessRate', 'pricingComponent', 'socialTariff', 'tou'])
                ->paginate($limit);
        }
        return $this->meterTariff->newQuery()
            ->with(['accessRate', 'pricingComponent', 'socialTariff', 'tou'])
            ->get();
    }
}
