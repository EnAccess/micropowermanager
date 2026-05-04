<?php

namespace App\Services;

use App\Http\Requests\CreateAgentCustomerRequest;
use App\Models\Agent;
use App\Models\City;
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

    /**
     * @return LengthAwarePaginator<int, Person>
     */
    public function list(Agent $agent): LengthAwarePaginator {
        return $this->scopedQuery($agent)->latest()
            ->orderByDesc('id')
            ->paginate(config('settings.paginate'));
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
            ->orWhere('surname', 'LIKE', '%'.$searchTerm.'%')->latest()
            ->orderByDesc('id')
            ->paginate($limit);
    }
}
