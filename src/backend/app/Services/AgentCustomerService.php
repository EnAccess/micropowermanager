<?php

namespace App\Services;

use App\Events\AccessRatePaymentInitialize;
use App\Http\Requests\AssignMeterToCustomerRequest;
use App\Http\Requests\CreateAgentCustomerRequest;
use App\Models\Agent;
use App\Models\City;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class AgentCustomerService {
    public function __construct(
        private Person $person,
        private City $city,
        private PersonService $personService,
        private GeographicalInformationService $geographicalInformationService,
        private AddressGeographicalInformationService $addressGeographicalInformationService,
        private MeterService $meterService,
        private DeviceService $deviceService,
        private MeterDeviceService $meterDeviceService,
    ) {}

    public function register(Agent $agent, CreateAgentCustomerRequest $request): Person {
        $cityId = $request->integer('city_id');
        $phone = $request->string('phone')->toString();
        $geoPoints = $request->string('geo_points')->toString();

        $city = $this->city->newQuery()->findOrFail($cityId);
        if ($city->mini_grid_id !== $agent->mini_grid_id) {
            throw ValidationException::withMessages(['city_id' => ['Selected city does not belong to the agent\'s mini-grid.']]);
        }

        if ($this->personService->getByPhoneNumber($phone) instanceof Person) {
            throw ValidationException::withMessages(['phone' => ['A customer with this phone number already exists.']]);
        }

        $request->merge(['is_customer' => 1]);
        $person = $this->personService->createFromRequest($request);

        if ($geoPoints !== '') {
            $address = $person->addresses()->where('is_primary', 1)->first();
            $geographicalInformation = $this->geographicalInformationService->make([
                'points' => $geoPoints,
            ]);
            $this->addressGeographicalInformationService->setAssigned($geographicalInformation);
            $this->addressGeographicalInformationService->setAssignee($address);
            $this->addressGeographicalInformationService->assign();
            $this->geographicalInformationService->save($geographicalInformation);
        }

        return $person->fresh(['addresses.city', 'addresses.geo']);
    }

    public function assignMeter(Agent $agent, Person $customer, AssignMeterToCustomerRequest $request): Meter {
        $primaryAddress = $customer->addresses()->where('is_primary', 1)->with('city')->first();
        if ($primaryAddress === null || $primaryAddress->city === null || $primaryAddress->city->mini_grid_id !== $agent->mini_grid_id) {
            throw ValidationException::withMessages(['customer' => ['Customer does not belong to the agent\'s mini-grid.']]);
        }

        $meter = $this->meterService->create([
            'serial_number' => $request->string('serial_number')->toString(),
            'manufacturer_id' => $request->integer('manufacturer_id'),
            'meter_type_id' => $request->integer('meter_type_id'),
            'tariff_id' => $request->integer('tariff_id'),
            'connection_group_id' => $request->integer('connection_group_id'),
            'connection_type_id' => $request->integer('connection_type_id'),
            'in_use' => 1,
        ]);

        $device = $this->deviceService->make([
            'person_id' => $customer->id,
            'device_serial' => $meter->serial_number,
        ]);
        $this->meterDeviceService->setAssigned($device);
        $this->meterDeviceService->setAssignee($meter);
        $this->meterDeviceService->assign();
        $this->deviceService->save($device);

        $geoPoints = $request->string('geo_points')->toString();
        if ($geoPoints !== '') {
            $geographicalInformation = $this->geographicalInformationService->make(['points' => $geoPoints]);
            $this->addressGeographicalInformationService->setAssigned($geographicalInformation);
            $this->addressGeographicalInformationService->setAssignee($primaryAddress);
            $this->addressGeographicalInformationService->assign();
            $this->geographicalInformationService->save($geographicalInformation);
        }

        event(new AccessRatePaymentInitialize($meter));

        return $meter->fresh(['tariff', 'device', 'meterType', 'connectionType', 'connectionGroup', 'manufacturer']);
    }

    /**
     * @return LengthAwarePaginator<int, Person>
     */
    public function list(Agent $agent): LengthAwarePaginator {
        return $this->scopedQuery($agent)->paginate(config('settings.paginate'));
    }

    public function findForAgent(Agent $agent, int $customerId): Person {
        return $this->scopedQuery($agent)->findOrFail($customerId);
    }

    /**
     * @return Builder<Person>
     */
    private function scopedQuery(Agent $agent): Builder {
        return $this->person->newQuery()->with([
            'devices',
            'addresses' => fn ($q) => $q->where('is_primary', 1)->with('city'),
        ])
            ->where('is_customer', 1)
            ->whereHas(
                'addresses',
                fn ($q) => $q->whereHas('city', fn ($q) => $q->where('mini_grid_id', $agent->mini_grid_id))
            );
    }

    /**
     * @return LengthAwarePaginator<int, Person>
     */
    public function search(string $searchTerm, int $limit, Agent $agent): LengthAwarePaginator {
        return $this->person->newQuery()->with(['addresses.city', 'devices'])->whereHas(
            'addresses',
            fn ($q) => $q->where('phone', 'LIKE', '%'.$searchTerm.'%')
        )->orWhereHas(
            'devices',
            fn ($q) => $q->where('device_serial', 'LIKE', '%'.$searchTerm.'%')
        )->orWhere('name', 'LIKE', '%'.$searchTerm.'%')
            ->orWhere('surname', 'LIKE', '%'.$searchTerm.'%')
            ->paginate($limit);
    }
}
