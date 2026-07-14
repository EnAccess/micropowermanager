<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonListRequest;
use App\Http\Requests\PersonRequest;
use App\Http\Resources\ApiResource;
use App\Models\Country;
use App\Services\AddressesService;
use App\Services\CountryService;
use App\Services\PersonAddressService;
use App\Services\PersonService;
use Dedoc\Scramble\Attributes\Example;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonController extends Controller {
    public function __construct(
        private AddressesService $addressService,
        private PersonService $personService,
        private PersonAddressService $personAddressService,
        private CountryService $countryService,
    ) {}

    /**
     * List people.
     *
     * To get a list of registered customers or non-customers, like the contact person of a meter manufacturer.
     */
    public function index(PersonListRequest $request): ApiResource {
        return $this->listPeople($request);
    }

    /**
     * Shared implementation for all people list routes (`index` and its deprecated aliases).
     * The aliases must not call `index` directly:
     * a route method calling another route method breaks Scramble's request documentation generation
     * ("Scope is not initialized for route").
     */
    private function listPeople(PersonListRequest $request): ApiResource {
        return ApiResource::make(
            $this->personService->getAll($request->integer('per_page', 15), $request->filters())
        );
    }

    /**
     * List people (legacy alias).
     *
     * Alias of `GET /api/people`, kept for backwards compatibility with older clients.
     * It accepts the same query parameters.
     *
     * @deprecated use `GET /api/people` instead
     */
    public function indexAll(PersonListRequest $request): ApiResource {
        return $this->listPeople($request);
    }

    /**
     * List people (customer registration app).
     *
     * Alias of `GET /api/people`, kept for backwards compatibility with the customer registration app.
     * It accepts the same query parameters.
     *
     * @deprecated use `GET /api/people` instead
     */
    #[Group('Customer Registration App')]
    public function indexForCustomerRegistrationApp(PersonListRequest $request): ApiResource {
        return $this->listPeople($request);
    }

    /**
     * Get person details.
     *
     * Displays the person with following relations
     * - Addresses
     * - Citizenship
     * - Role
     * - Meter list.
     */
    public function show(int $personId): ApiResource {
        return ApiResource::make($this->personService->getDetails($personId, true));
    }

    /**
     * Create a person.
     */
    public function store(PersonRequest $request): JsonResponse {
        try {
            $customerType = $request->input('customer_type');
            $addressData = $this->addressService->createAddressDataFromRequest($request);
            $personData = $this->personService->createPersonDataFromRequest($request);
            $miniGridId = $request->input('mini_grid_id');
            DB::connection('tenant')->beginTransaction();
            if ($this->personService->isMaintenancePerson($customerType)) {
                $personData['mini_grid_id'] = $miniGridId;
                $person = $this->personService->createMaintenancePerson($personData);
            } else {
                $country = $this->countryService->getByCode($request->input('country_code'));
                $person = $this->personService->create($personData);

                if ($country instanceof Country) {
                    $person = $this->personService->addCitizenship($person, $country);
                }
            }

            $address = $this->addressService->make($addressData);
            $this->personAddressService->setAssignee($person);
            $this->personAddressService->setAssigned($address);
            $this->personAddressService->assign();
            $this->addressService->save($address);
            DB::connection('tenant')->commit();

            return ApiResource::make($person)->response()->setStatusCode(201);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }
    }

    /**
     * Update a person.
     *
     * Updates the given parameter of that person.
     */
    public function update(
        int $personId,
        PersonRequest $request,
    ): ApiResource {
        $person = $this->personService->getById($personId);
        $personData = $request->all();

        return ApiResource::make($this->personService->update($person, $personData));
    }

    /**
     * List person transactions.
     *
     * The list of all transactions(paginated) which belong to that person.
     * Each page contains 7 entries of the last transaction.
     */
    public function transactions(
        int $personId,
    ): ApiResource {
        $person = $this->personService->getById($personId);

        return ApiResource::make($this->personService->getPersonTransactions($person));
    }

    /**
     * Search people.
     *
     * Searches the person list with a "begins with" match:
     * a person is returned when at least one of the following attributes starts with the search term.
     * - phone number
     * - device serial number
     * - name
     * - surname.
     *
     * The leading `+` of a phone number is optional.
     * If provided (to distinguish the term from a device serial number),
     * it has to be URL-encoded as `%2B`.
     */
    #[QueryParameter('term', description: 'The search term. Matched against the beginning of each searchable attribute.', type: 'string', examples: [
        'name' => new Example('John Doe', summary: 'By name'),
        'phone' => new Example('49123456', summary: 'By phone number (without leading +)'),
        'serial' => new Example('47001231', summary: 'By device serial number'),
        'encoded-phone' => new Example('%2B49123456', summary: 'By phone number (URL-encoded leading +)'),
    ])]
    public function search(
        Request $request,
    ): ApiResource {
        $term = $request->string('term')->toString();
        $paginate = $request->integer('paginate', 1);
        $perPage = $request->integer('per_page', 15);

        return ApiResource::make($this->personService->searchPerson($term, $paginate, $perPage));
    }

    /**
     * Delete a person.
     *
     * Deletes that person with all his/her relations from the database. The person model uses soft deletes.
     * That means the original record wont be deleted but all mentioned relations will be removed permanently.
     *
     * @throws \Exception
     */
    public function destroy(
        int $personId,
    ): JsonResponse {
        $person = $this->personService->getById($personId);

        $deleted = $this->personService->delete($person);

        if (!$deleted) {
            return response()->json([
                'message' => 'Failed to delete person',
            ], 500);
        }

        return ApiResource::make([
            'message' => 'Person deleted successfully',
            'data' => $person,
        ])->response()->setStatusCode(200);
    }
}
