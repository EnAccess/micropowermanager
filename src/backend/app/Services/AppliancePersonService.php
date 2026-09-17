<?php

namespace App\Services;

use App\Events\NewLogEvent;
use App\Models\Address\Address;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\Device;
use App\Models\Log;
use App\Models\MainSettings;
use App\Models\Person\Person;
use App\Services\Interfaces\IAssociative;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @implements IBaseService<AppliancePerson>
 * @implements IAssociative<AppliancePerson>
 */
class AppliancePersonService implements IBaseService, IAssociative {
    /** @use HasCrudOperations<AppliancePerson> */
    use HasCrudOperations;

    public function __construct(
        private MainSettings $mainSettings,
        private AppliancePerson $appliancePerson,
        private Device $device,
        private UserService $userService,
        private AppliancePaymentService $appliancePaymentService,
    ) {}

    protected function crudModel(): AppliancePerson {
        return $this->appliancePerson;
    }

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

    public function getSoldApplianceDetails(int $appliancePersonId): AppliancePerson {
        $appliancePerson = $this->appliancePerson->newQuery()->withTrashed()
            ->with('appliance', 'rates.logs', 'logs.owner', 'device')
            ->where('id', '=', $appliancePersonId)
            ->first();

        $appliancePerson = $this->sumTotalPaymentsAndTotalRemainingAmount($appliancePerson);
        $appliancePerson['rateType'] = $this->appliancePaymentService->getRateType($appliancePerson);

        return $appliancePerson;
    }

    /**
     * @return Collection<int, AppliancePerson>
     */
    public function getSoldAppliancesForPerson(int $personId): Collection {
        return $this->appliancePerson->newQuery()->withTrashed()
            ->with('appliance.applianceType', 'rates.logs', 'logs.owner')
            ->where('person_id', $personId)
            ->get();
    }

    /**
     * @return LengthAwarePaginator<int, ApplianceRate>
     */
    public function getRates(AppliancePerson $appliancePerson, int $perPage): LengthAwarePaginator {
        return $appliancePerson->rates()
            ->with('logs.owner')
            ->oldest('due_date')
            ->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, Log>
     */
    public function getLogs(AppliancePerson $appliancePerson, int $perPage): LengthAwarePaginator {
        return $appliancePerson->logs()
            ->with('owner')
            ->latest()
            ->paginate($perPage);
    }

    public function deleteWithDeviceRelease(AppliancePerson $appliancePerson, int $creatorId): AppliancePerson {
        if ($appliancePerson->device_serial) {
            $this->device->newQuery()
                ->where('device_serial', $appliancePerson->device_serial)
                ->update(['person_id' => null]);
        }

        $creatorName = $this->userService->getById($creatorId)->name ?? 'Unknown';
        event(new NewLogEvent([
            'user_id' => $creatorId,
            'affected' => $appliancePerson,
            'action' => "User {$creatorName} deleted the sold appliance",
        ]));

        $appliancePerson->delete();

        return $appliancePerson;
    }

    private function sumTotalPaymentsAndTotalRemainingAmount(AppliancePerson $appliancePerson): AppliancePerson {
        $rates = collect($appliancePerson->rates);
        $appliancePerson['totalRemainingAmount'] = 0;
        $appliancePerson['totalPayments'] = 0;

        $rates->map(function ($rate) use ($appliancePerson) {
            $appliancePerson['totalRemainingAmount'] += $rate->remaining;
            if ($rate->remaining !== $rate->rate_cost) {
                $appliancePerson['totalPayments'] += $rate->rate_cost - $rate->remaining;
            }
        });

        return $appliancePerson;
    }

    public function getById(int $id): AppliancePerson {
        return $this->appliancePerson->newQuery()->withTrashed()->findOrFail($id);
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
     * @return Collection<int, AppliancePerson>
     */
    public function getAllForExport(?string $paymentType = null, ?int $personId = null): Collection {
        return $this->appliancePerson->newQuery()
            ->with(['person', 'appliance.applianceType'])
            ->when($paymentType !== null, fn ($query) => $query->where('payment_type', $paymentType))
            ->when($personId !== null, fn ($query) => $query->where('person_id', $personId))
            ->get();
    }

    /**
     * @return SupportCollection<int, int>
     */
    public function getLoanIdsForCustomerId(int $customerId): SupportCollection {
        return $this->appliancePerson->newQuery()
            ->where('person_id', $customerId)
            ->where('device_serial')
            ->orWhere('device_serial', '')->pluck('id');
    }

    public function getBySerialNumber(string $serialNumber): ?AppliancePerson {
        return $this->appliancePerson->newQuery()->where('device_serial', $serialNumber)->first();
    }

    /**
     * Payable devices (non-deleted plans only) belonging to whichever customer(s) have this
     * phone number on any of their addresses. A phone isn't unique to one address or one
     * customer, so this returns the union across every match rather than erroring.
     *
     * @return Collection<int, AppliancePerson>
     */
    public function getPayableByPhone(string $normalizedPhone): Collection {
        $personIds = Address::query()
            ->whereMorphedTo('owner', Person::class)
            ->where('phone', $normalizedPhone)
            ->pluck('owner_id');

        if ($personIds->isEmpty()) {
            return new Collection();
        }

        return $this->appliancePerson->newQuery()
            ->with('appliance.applianceType', 'person', 'rates')
            ->whereIn('person_id', $personIds)
            ->whereNotNull('device_serial')
            ->where('device_serial', '!=', '')
            ->get();
    }

    public function getCountByClusterId(int $clusterId): int {
        return $this->appliancePerson->newQuery()
            ->whereHas('person', function ($q) use ($clusterId) {
                $q->whereHas('addresses', function ($q) use ($clusterId) {
                    $q->where('is_primary', 1)
                        ->whereHas('city', function ($q) use ($clusterId) {
                            $q->whereHas('cluster', function ($q) use ($clusterId) {
                                $q->where('clusters.id', $clusterId);
                            });
                        });
                });
            })
            ->count();
    }
}
