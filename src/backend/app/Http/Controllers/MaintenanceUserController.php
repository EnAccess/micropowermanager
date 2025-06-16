<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceRequest;
use App\Http\Resources\ApiResource;
use App\Models\MaintenanceUsers;
use App\Models\Person\Person;
use App\Services\PersonService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Inensus\BulkRegistration\Services\AddressService;

class MaintenanceUserController extends Controller {
    public function __construct(
        private MaintenanceUsers $maintenanceUsers,
        private Person $person,
        private PersonService $personService,
        private AddressService $addressService,
    ) {}

    public function index(): ApiResource {
        $maintenance_user_list = $this->maintenanceUsers::with('person')->get();

        return new ApiResource($maintenance_user_list);
    }

    /**
     * @param MaintenanceRequest $request
     *
     * @return JsonResponse
     */
    public function store(MaintenanceRequest $request): JsonResponse {
        $phone = $request->get('phone');

        try {
            $person = $this->person->newQuery()->whereHas(
                'addresses',
                static function ($q) use ($phone) {
                    $q->where('phone', $phone);
                }
            )->firstOrFail();
        } catch (ModelNotFoundException) {
            $personData = $this->personService->createPersonDataFromRequest($request);
            $person = $this->personService->createMaintenancePerson($personData);
            $this->addressService->createForPerson($person->getId(), $request->getCityId(), $request->getPhone(), $request->getEmail(), $request->getStreet(), true);
        }

        $maintenanceUser = $this->maintenanceUsers::query()->create(
            [
                'person_id' => $person->id,
                'mini_grid_id' => $request->get('mini_grid_id'),
            ]
        );

        return
            (new ApiResource($maintenanceUser))
                ->response()
                ->setStatusCode(201);
    }
}
