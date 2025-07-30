<?php

namespace App\Services;

use App\Events\NewLogEvent;
use App\Models\AssetPerson;
use App\Models\MainSettings;
use App\Services\Interfaces\IAssociative;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @implements IBaseService<AssetPerson>
 * @implements IAssociative<AssetPerson>
 */
class AppliancePersonService implements IBaseService, IAssociative {
    public function __construct(
        private MainSettings $mainSettings,
        private AssetPerson $assetPerson,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function make(array $data): AssetPerson {
        return $this->assetPerson->newQuery()->make($data);
    }

    public function save($appliancePerson): bool {
        return $appliancePerson->save();
    }

    public function createLogForSoldAppliance(AssetPerson $assetPerson, float $cost, float $preferredPrice): void {
        $currency = $this->getCurrencyFromMainSettings();

        event(new NewLogEvent([
            'user_id' => auth('api')->user()->id,
            'affected' => $assetPerson,
            'action' => 'Appliance is sold to '.$cost.' '.$currency.
                ' instead of Preferred Price ('.$preferredPrice.' '.$currency.')',
        ]));
    }

    public function getCurrencyFromMainSettings(): string {
        $mainSettings = $this->mainSettings->newQuery()->first();

        return $mainSettings === null ? '€' : $mainSettings->currency;
    }

    public function getApplianceDetails(int $applianceId): AssetPerson {
        $appliance = $this->assetPerson::with('asset', 'rates.logs', 'logs.owner')
            ->where('id', '=', $applianceId)
            ->first();

        return $this->sumTotalPaymentsAndTotalRemainingAmount($appliance);
    }

    private function sumTotalPaymentsAndTotalRemainingAmount(AssetPerson $appliance): AssetPerson {
        /** @var SupportCollection<int, mixed> $rates */
        $rates = collect($appliance->rates);
        $appliance['totalRemainingAmount'] = 0;
        $appliance['totalPayments'] = 0;

        $rates->map(function ($rate) use ($appliance) {
            $appliance['totalRemainingAmount'] += $rate->remaining;
            if ($rate->remaining !== $rate->rate_cost) {
                $appliance['totalPayments'] += $rate->rate_cost - $rate->remaining;
            }
        });

        return $appliance;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<AssetPerson>
     */
    public function getLoansForCustomerId(int $customerId) {
        return $this->assetPerson->newQuery()->where('person_id', $customerId);
    }

    public function getById(int $id): AssetPerson {
        throw new \Exception('Method getById() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): AssetPerson {
        throw new \Exception('Method create() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): AssetPerson {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, AssetPerson>|LengthAwarePaginator<AssetPerson>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->assetPerson->newQuery()->with(['person.devices'])->paginate($limit);
        }

        return $this->assetPerson->newQuery()->with(['person.devices'])->get();
    }

    /**
     * @return SupportCollection<int, int>
     */
    public function getLoanIdsForCustomerId(int $customerId): SupportCollection {
        return $this->assetPerson->newQuery()
            ->where('person_id', $customerId)
            ->where('device_serial', null)
            ->orWhere('device_serial', '')->pluck('id');
    }

    public function getBySerialNumber(string $serialNumber): ?AssetPerson {
        return $this->assetPerson->newQuery()->where('device_serial', $serialNumber)->first();
    }
}
