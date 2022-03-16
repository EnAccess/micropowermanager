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


    private $agent;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    public function list(Agent $agent): LengthAwarePaginator
    {

        $miniGridId = $agent->mini_grid_id;
        return Person::with(
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

    /**
     * @param Request|array|string $searchTerm
     * @param Request|array|int|string $paginate
     *
     * @return LengthAwarePaginator|Builder[]|Collection
     *
     */
    public function searchAgentsCustomers($searchTerm, $paginate, $agent)
    {

        $miniGridId = $agent->mini_grid_id;
        $personQuery = Person::with(
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
            );

        if ($paginate === 1) {
            return $personQuery->paginate(15);
        }
        return $personQuery->get();
    }
}
