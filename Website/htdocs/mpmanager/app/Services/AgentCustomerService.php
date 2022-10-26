<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Person\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class AgentCustomerService
{
    public function __construct(private Agent $agent, private Person $person)
    {
    }

    public function list(Agent $agent): LengthAwarePaginator
    {
        $miniGridId = $agent->mini_grid_id;

        return $this->person->newQuery()->with(
            [
                'addresses' => function ($q) {
                    return $q->where('is_primary', 1);
                },
                'addresses.city',
                'meters.meter',
            ]
        )
            ->where('is_customer', 1)
            ->whereHas(
                'addresses',
                function ($q) use ($miniGridId) {
                    $q->whereHas(
                        'city',
                        function ($q) use ($miniGridId) {
                            $q->where('mini_grid_id', $miniGridId);
                        }
                    );
                }
            )
            ->paginate(config('settings.paginate'));
    }


    public function search($searchTerm, $limit, $agent)
    {
        $miniGridId = $agent->mini_grid_id;

        return $this->person->newQuery()->with(
            [
                'addresses' => function ($q) {
                    return $q->where('is_primary', 1);
                },
                'addresses.city',
                'meters.meter',

            ]
        )->where('is_customer', 1)
            ->where('name', 'LIKE', '%' . $searchTerm . '%')
            ->whereHas(
                'addresses.city',
                function ($q) use ($searchTerm, $miniGridId) {
                    $q->where('mini_grid_id', $miniGridId);
                }
            )
            ->orWhere('surname', 'LIKE', '%' . $searchTerm . '%')
            ->orWhereHas(
                'addresses',
                function ($q) use ($searchTerm) {
                    $q->where('email', 'LIKE', '%' . $searchTerm . '%');
                    $q->where('phone', 'LIKE', '%' . $searchTerm . '%');
                }
            )
            ->orWhereHas(
                'addresses.city',
                function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', '%' . $searchTerm . '%');
                }
            )
            ->orWhereHas(
                'meters.meter',
                function ($q) use ($searchTerm) {
                    $q->where('serial_number', 'LIKE', '%' . $searchTerm . '%');
                }
            )->paginate($limit);
    }
}
