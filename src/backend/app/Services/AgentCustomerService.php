<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Person\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AgentCustomerService {
    public function __construct(private Person $person) {}

    /**
     * @return LengthAwarePaginator<Person>
     */
    public function list(Agent $agent): LengthAwarePaginator {
        $miniGridId = $agent->mini_grid_id;

        return $this->person->newQuery()->with([
            'devices',
            'addresses' => fn ($q) => $q->where('is_primary', 1)->with('city'),
        ])
            ->where('is_customer', 1)
            ->whereHas(
                'addresses',
                fn ($q) => $q->whereHas('city', fn ($q) => $q->where('mini_grid_id', $miniGridId))
            )
            ->paginate(config('settings.paginate'));
    }

    /**
     * @return LengthAwarePaginator<Person>
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
