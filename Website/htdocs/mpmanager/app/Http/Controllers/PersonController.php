<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonRequest;
use App\Http\Resources\ApiResource;

use App\Models\Person\Person;
use App\Services\PersonService;
use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Class PersonController
 *
 * @package App\Http\Controllers
 *
 * @group People
 */
class PersonController extends Controller
{
    private $personService;

    public function __construct(PersonService $personService)
    {
        $this->personService = $personService;
    }

    /**
     * List customer/other
     * [ To get a list of registered customers or non-customer like contact person of Meter Manufacturer. ]
     *
     * @urlParam is_customer int optinal. To get a list of customers or non customer. Default : 1
     *
     * @responseFile responses/people/people.list.json
     *
     * @return ApiResource
     */
    public function index(): ApiResource
    {
        $customerType = request('is_customer') ?? 1;
        return new ApiResource(
            Person::with(
                [
                'addresses' => function ($q) {
                    return $q->where('is_primary', 1);
                },
                'addresses.city',
                'meters.meter',
                ]
            )
                ->where('is_customer', $customerType)
                ->paginate(config('settings.paginate'))
        );
    }

    /**
     * Lists all people
     * Lists all registered people without any filtering and pagination
     * The list contains no relations
     *
     * @responseFile responses/people/people.list.all.json
     * @return       ApiResource
     */
    public function list()
    {
        return new ApiResource(
            Person::all()
        );
    }

    /**
     * Detail
     * Displays the person with following relations
     * - Addresses
     * - Citizenship
     * - Role
     * - Meter list
     *
     * @param Person $person
     *
     * @return ApiResource
     *
     * @apiResourceModel App\Models\Person\Person
     * @responseFile     responses/people/people.detail.json
     */
    public function show(Person $person): ApiResource
    {
        $personData = $this->personService->getDetails((int)$person->id, true);
        return new ApiResource(
            $personData
        );
    }

    /**
     * Create
     *
     * @param PersonRequest $request
     *
     * @return JsonResponse
     */
    public function store(PersonRequest $request): JsonResponse
    {
        $customerType = $request->get('customer_type');
        if ($customerType === null || $customerType === 'customer') {
            $person = $this->personService->createFromRequest($request);
        } else {
            $person = $this->personService->createMaintenancePerson($request);
        }

        return (new ApiResource(
            $person
        )
        )->response()->setStatusCode(201);
    }

    /**
     * Update
     * Updates the given parameter of that person
     *
     * @urlParam  id required The ID of the person to update
     * @bodyParam title string. The title of the person. Example: Dr.
     * @bodyParam name string. The title of the person. Example: Dr.
     * @bodyParam surname string. The title of the person. Example: Dr.
     * @bodyParam birth_date string. The title of the person. Example: Dr.
     * @bodyParam sex string. The title of the person. Example: Dr.
     * @bodyParam education string. The title of the person. Example: Dr.
     * @param     Person $person
     * @return    ApiResource
     *
     * @apiResourceModel App\Models\Person\Person
     * @responseFile     responses/people/person.update.json
     */
    public function update(Person $person): ApiResource
    {
        $person->title = request('title');
        $person->name = request('name');
        $person->surname = request('surname');
        $person->birth_date = request('birth_date');
        $person->sex = request('sex');
        $person->education = request('education');
        $person->save();
        return new ApiResource($person);
    }

    /**
     * Transactions
     * The list of all transactions(paginated) which belong to that person.
     * Each page contains 7 entries of the last transaction
     *
     * @param $personId
     *
     * @return ApiResource
     *
     * @bodyParam    person_id int required the ID of the person. Example: 2
     * @responseFile responses/people/person.transaction.list.json
     */
    public function transactions($personId)
    {
        $person = Person::find($personId);
        return new ApiResource($person->transactions()->with('transaction.token')->latest()->paginate(7));
    }

    /**
     * Search
     * Searches in person list according to the search term.
     *  Term could be one of the following attributes;
     * - phone number
     * - meter serial number
     * - name
     * - surname
     *
     * @urlParam term  The ID of the post. Example: John Doe
     * @urlParam paginage int The page number. Example:1
     *
     * @return       ApiResource
     * @responseFile responses/people/people.search.json
     */
    public function search()
    {
        $term = request('term');
        $paginate = request('paginate') ?? 1;
        return new ApiResource($this->personService->searchPerson($term, $paginate));
    }

    /**
     * Addresses
     * A list of registered addresses for that person
     *
     * @bodyParam    person int required the ID of the person. Example: 2
     * @responseFile responses/people/person.addresses.list.json
     * @param        Person $person
     *
     * @return ApiResource
     *
     * @apiResourceModel \App\Models\Person\Person
     */
    public function addresses(Person $person): ApiResource
    {
        $addresses = $person->addresses()->with('city', 'geo')->orderBy('is_primary', 'DESC')->paginate(5);
        return new ApiResource($addresses);
    }

    /**
     * Delete
     * Deletes that person with all his/her relations from the database. The person model uses soft deletes.
     * That means the orinal record wont be deleted but all mentioned relations will be removed permanently
     *
     * @urlParam person required The ID of the person. Example:1
     * @param    Person $person
     * @return   ApiResource
     * @throws   Exception
     *
     * @apiResourceModel App\Models\Person\Person
     */
    public function destroy(Person $person): ApiResource
    {
        $deletedPerson = $person->delete();
        return new ApiResource($deletedPerson);
    }
}
