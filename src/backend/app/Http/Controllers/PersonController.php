<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonRequest;
use App\Http\Resources\ApiResource;
use App\Models\Country;
use App\Services\AddressesService;
use App\Services\CountryService;
use App\Services\PersonAddressService;
use App\Services\PersonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class PersonController.
 *
 * @group People
 */
class PersonController extends Controller {
    public function __construct(
        private AddressesService $addressService,
        private PersonService $personService,
        private PersonAddressService $personAddressService,
        private CountryService $countryService,
    ) {}

    /**
     * List customer/other
     * [ To get a list of registered customers or non-customer like contact person of Meter Manufacturer. ].
     *
     * @urlParam is_customer int optinal. To get a list of customers or non customer. Default : 1
     * @urlParam agent_id int optional. To gget a list of customers of a specific agent.
     * @urlParam limit int optional. The number of items per page.
     * @urlParam active_customer int optional. To get a list of active customers. Default: 0
     *
     * @responseFile responses/people/people.list.json
     */
    public function index(Request $request): ApiResource {
        $customerType = $request->input('is_customer', 1);
        $limit = $request->input('limit', config('settings.paginate'));
        $agentId = $request->input('agent_id');
        $activeCustomer = $request->has('active_customer') ? (bool) $request->input('active_customer') : null;

        return ApiResource::make($this->personService->getAll($limit, $customerType, $agentId, $activeCustomer));
    }

    /**
     * Detail
     * Displays the person with following relations
     * - Addresses
     * - Citizenship
     * - Role
     * - Meter list.
     *
     * @apiResourceModel App\Models\Person\Person
     *
     * @responseFile     responses/people/people.detail.json
     */
    public function show(int $personId): ApiResource {
        return ApiResource::make($this->personService->getDetails($personId, true));
    }

    /**
     * Create.
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
                $country = $this->countryService->getByCode($request->get('country_code'));
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
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Update
     * Updates the given parameter of that person.
     *
     * @urlParam  id required The ID of the person to update
     *
     * @bodyParam title string. The title of the person. Example: Dr.
     * @bodyParam name string. The title of the person. Example: Dr.
     * @bodyParam surname string. The title of the person. Example: Dr.
     * @bodyParam birth_date string. The title of the person. Example: Dr.
     * @bodyParam sex string. The title of the person. Example: Dr.
     * @bodyParam education string. The title of the person. Example: Dr.
     *
     * @apiResourceModel App\Models\Person\Person
     *
     * @responseFile     responses/people/person.update.json
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
     * Transactions
     * The list of all transactions(paginated) which belong to that person.
     * Each page contains 7 entries of the last transaction.
     *
     * @bodyParam    person_id int required the ID of the person. Example: 2
     *
     * @responseFile responses/people/person.transaction.list.json
     */
    public function transactions(
        int $personId,
    ): ApiResource {
        $person = $this->personService->getById($personId);

        return ApiResource::make($this->personService->getPersonTransactions($person));
    }

    /**
     * Search
     * Searches in person list according to the search term.
     *  Term could be one of the following attributes;
     * - phone number
     * - meter serial number
     * - name
     * - surname.
     *
     * @urlParam term  The ID of the post. Example: John Doe
     * @urlParam paginage int The page number. Example:1
     *
     * @responseFile responses/people/people.search.json
     */
    public function search(
        Request $request,
    ): ApiResource {
        $term = $request->input('term');
        $paginate = $request->input('paginate', 1);

        return ApiResource::make($this->personService->searchPerson($term, $paginate));
    }

    /**
     * Delete
     * Deletes that person with all his/her relations from the database. The person model uses soft deletes.
     * That means the orinal record wont be deleted but all mentioned relations will be removed permanently.
     *
     * @urlParam person required The ID of the person. Example:1
     *
     * @throws \Exception
     *
     * @apiResourceModel App\Models\Person\Person
     */
    public function destroy(
        int $personId,
    ): ApiResource {
        $person = $this->personService->getById($personId);

        return ApiResource::make($this->personService->delete($person));
    }
}
