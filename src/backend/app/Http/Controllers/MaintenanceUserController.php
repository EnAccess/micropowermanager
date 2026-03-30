<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceRequest;
use App\Http\Resources\ApiResource;
use App\Models\Person\Person;
use App\Plugins\BulkRegistration\Services\AddressService;
use App\Services\PersonService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class MaintenanceUserController extends Controller {
    public function __construct(
        private Person $person,
        private PersonService $personService,
        private AddressService $addressService,
    ) {}

    public function index(): ApiResource {
        $maintenance_user_list = $this->personService->getAllMaintenanceUsers();

        return new ApiResource($maintenance_user_list);
    }

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
            // Create maintenance person with mini_grid_id if provided
            if ($request->has('mini_grid_id')) {
                $personData['mini_grid_id'] = $request->get('mini_grid_id');
                $person = $this->personService->createMaintenancePerson($personData);
            } else {
                $person = $this->personService->createMaintenancePerson($personData);
            }
            $this->addressService->createForPerson($person->getId(), $request->getCityId(), $request->getPhone(), $request->getEmail(), $request->getStreet(), true);
        }

        // Ensure the person is marked as maintenance type
        if ($person->type !== 'maintenance') {
            $person->type = 'maintenance';
            if ($request->has('mini_grid_id') && !$person->mini_grid_id) {
                $person->mini_grid_id = $request->get('mini_grid_id');
            }
            $person->save();
        }

        $maintenanceUser = $person;

        return
            (new ApiResource($maintenanceUser))
                ->response()
                ->setStatusCode(201);
    }
}
