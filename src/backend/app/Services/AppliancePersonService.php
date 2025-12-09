<?php

namespace App\Services;

use App\Events\NewLogEvent;
use App\Models\AppliancePerson;
use App\Models\MainSettings;
use App\Services\Interfaces\IAssociative;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @implements IBaseService<AppliancePerson>
 * @implements IAssociative<AppliancePerson>
 */
class AppliancePersonService implements IBaseService, IAssociative {
    public function __construct(
        private MainSettings $mainSettings,
        private AppliancePerson $appliancePerson,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function make(array $data): AppliancePerson {
        return $this->appliancePerson->newQuery()->make($data);
    }

    public function save($appliancePerson): bool {
        return $appliancePerson->save();
    }

    public function createLogForSoldAppliance(AppliancePerson $appliancePerson, float $cost, float $preferredPrice): void {
        $currency = $this->getCurrencyFromMainSettings();

        event(new NewLogEvent([
            'user_id' => auth('api')->user()->id,
            'affected' => $appliancePerson,
            'action' => 'Appliance is sold to '.$cost.' '.$currency.
                ' instead of Preferred Price ('.$preferredPrice.' '.$currency.')',
        ]));
    }

    public function getCurrencyFromMainSettings(): string {
        $mainSettings = $this->mainSettings->newQuery()->first();

        return $mainSettings === null ? '€' : $mainSettings->currency;
    }

    public function getApplianceDetails(int $applianceId): AppliancePerson {
        $appliance = $this->appliancePerson::with('appliance', 'rates.logs', 'logs.owner', 'device')
            ->where('id', '=', $applianceId)
            ->first();

        return $this->sumTotalPaymentsAndTotalRemainingAmount($appliance);
    }

    private function sumTotalPaymentsAndTotalRemainingAmount(AppliancePerson $appliance): AppliancePerson {
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
     * @return Builder<AppliancePerson>
     */
    public function getLoansForCustomerId(int $customerId) {
        return $this->appliancePerson->newQuery()->where('person_id', $customerId);
    }

    public function getById(int $id): AppliancePerson {
        throw new \Exception('Method getById() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): AppliancePerson {
        throw new \Exception('Method create() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): AppliancePerson {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, AppliancePerson>|LengthAwarePaginator<int, AppliancePerson>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->appliancePerson->newQuery()->with(['person.devices'])->paginate($limit);
        }

        return $this->appliancePerson->newQuery()->with(['person.devices'])->get();
    }

    /**
     * @return SupportCollection<int, int>
     */
    public function getLoanIdsForCustomerId(int $customerId): SupportCollection {
        return $this->appliancePerson->newQuery()
            ->where('person_id', $customerId)
            ->where('device_serial', null)
            ->orWhere('device_serial', '')->pluck('id');
    }

    public function getBySerialNumber(string $serialNumber): ?AppliancePerson {
        return $this->appliancePerson->newQuery()->where('device_serial', $serialNumber)->first();
    }
}
