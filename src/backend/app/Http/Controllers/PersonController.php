<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonRequest;
use App\Http\Resources\ApiResource;
use App\Models\Country;
use App\Services\AddressesService;
use App\Services\CountryService;
use App\Services\PersonAddressService;
use App\Services\PersonService;
use Dedoc\Scramble\Attributes\Example;
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
    #[QueryParameter('is_customer', description: 'To get a list of customers or non customer.', type: 'int', default: 1)]
    #[QueryParameter('agent_id', description: 'To get a list of customers of a specific agent.', type: 'int')]
    #[QueryParameter('per_page', description: 'The number of items per page.', type: 'int', default: 15)]
    #[QueryParameter('active_customer', description: 'To get a list of active customers.', type: 'int', default: 0)]
    #[QueryParameter('city_id', description: 'Filter by primary address city/village id.', type: 'int')]
    #[QueryParameter('total_paid_min', description: 'Minimum total paid amount for the customer.', type: 'float')]
    #[QueryParameter('total_paid_max', description: 'Maximum total paid amount for the customer.', type: 'float')]
    #[QueryParameter('latest_payment_from', description: 'ISO date string for minimum latest payment date.', type: 'string')]
    #[QueryParameter('latest_payment_to', description: 'ISO date string for maximum latest payment date.', type: 'string')]
    #[QueryParameter('registration_from', description: 'ISO date string for minimum registration date.', type: 'string')]
    #[QueryParameter('registration_to', description: 'ISO date string for maximum registration date.', type: 'string')]
    #[QueryParameter('device_type', description: 'Filter by device/appliance type.', type: 'string')]
    public function index(Request $request): ApiResource {
        $customerType = $request->input('is_customer', 1);
        $perPage = $request->input('per_page', 15);
        $agentId = $request->input('agent_id');
        $activeCustomer = $request->has('active_customer') ? (bool) $request->input('active_customer') : null;
        $cityId = $request->input('city_id');
        $totalPaidMin = $request->input('total_paid_min');
        $totalPaidMax = $request->input('total_paid_max');
        $latestPaymentFrom = $request->input('latest_payment_from');
        $latestPaymentTo = $request->input('latest_payment_to');
        $registrationFrom = $request->input('registration_from');
        $registrationTo = $request->input('registration_to');
        $deviceType = $request->input('device_type');

        return ApiResource::make(
            $this->personService->getAll(
                $perPage,
                $customerType,
                $agentId,
                $activeCustomer,
                $cityId,
                $totalPaidMin,
                $totalPaidMax,
                $latestPaymentFrom,
                $latestPaymentTo,
                $registrationFrom,
                $registrationTo,
                $deviceType,
            )
        );
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
