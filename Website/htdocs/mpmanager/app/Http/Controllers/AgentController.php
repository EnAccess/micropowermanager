<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentRequest;
use App\Http\Resources\ApiResource;
use App\Models\Agent;
use App\Services\AddressesService;
use App\Services\AgentService;
use App\Services\CountryService;
use App\Services\MaintenanceUserService;
use App\Services\PersonAddressService;
use App\Services\PersonService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AgentController extends Controller
{

    public function __construct(
        private AgentService $agentService,
        private AddressesService $addressService,
        private PersonService $personService,
        private PersonAddressService $personAddressService,
        private CountryService $countryService
    ) {

    }

    public function index(Request $request): ApiResource
    {
        $limit = $request->input('limit');

        return ApiResource::make($this->agentService->getAll($limit));
    }

    public function show($agentId, Request $request): ApiResource
    {
        return ApiResource::make($this->agentService->getById($agentId));
    }

    public function store(CreateAgentRequest $request): ApiResource
    {

        $addressData = $this->addressService->createAddressDataFromRequest($request);
        $personData = $this->personService->createPersonDataFromRequest($request);
        $country = $this->countryService->getByCode($request->get('nationality'));
        $agentData = [
            'password' => $request['password'],
            'email' => $request['email'],
            'mini_grid_id' => $request['city_id'],
            'agent_commission_id' => $request['agent_commission_id'],
            'device_id' => '-',
            'fire_base_token' => '-',
            'connection'=> auth('api')->user()->company->database->database_name
        ];

        return ApiResource::make($this->agentService->create(
            $agentData,
            $addressData,
            $personData,
            $country,
            $this->addressService,
            $this->countryService,
            $this->personService,
            $this->personAddressService
        ));
    }

    public function update($agentId, Request $request): ApiResource
    {
        $agent = $this->agentService->getById($agentId);
        $agentData = $request->all();

        return ApiResource::make($this->agentService->update($agent, $agentData, $this->personService));
    }

    public function destroy($agentId, Request $request): ApiResource
    {
        $agent = $this->agentService->getById($agentId);

        return ApiResource::make($this->agentService->delete($agent));
    }


    public function search(Request $request): ApiResource
    {
        $term = $request->input('term');
        $paginate = $request->input('paginate', 1);

        return ApiResource::make($this->agentService->searchAgent($term, $paginate));
    }

    /**
     * @return Response
     */
    public function resetPassword(Request $request, Response $response)
    {
        $responseMessage = $this->agentService->resetPassword($request->input('email'));

        if ($responseMessage === 'Invalid email.') {
            return $response->setStatusCode(422)->setContent(
                [
                    'data' => [
                        'message' => $responseMessage,
                        'status_code' => 400
                    ]
                ]
            );
        }

        return $response->setStatusCode(200)->setContent(
            [
                'data' => [
                    'message' => $responseMessage,
                    'status_code' => 200
                ]
            ]
        );
    }

    public function setFirebaseToken(Request $request): ApiResource
    {
        $agent = Agent::find(auth('agent_api')->user()->id);
        $this->agentService->setFirebaseToken($agent, $request->input('fire_base_token'));

        return new ApiResource($agent->fresh());
    }


    /**
     * @param Request $request
     * @param Response $response
     * @return AgentController|Response|object
     */
    public function showDashboardBoxes(Request $request, Response $response)
    {
        $agent = Agent::find(auth('agent_api')->user()->id);
        $average = $this->agentService->getTransactionAverage($agent);
        $since = $this->agentService->getLastReceiptDate($agent);
        return $response->setStatusCode(200)->setContent(
            [
                'data' => [
                    'balance' => $agent->balance,
                    'profit' => $agent->commission_revenue,
                    'dept' => $agent->due_to_energy_supplier,
                    'average' => $average,
                    'since' => $since,
                    'status_code' => 200
                ]
            ]
        );
    }

    public function showBalanceHistories(Request $request, Response $response)
    {
        $agent = Agent::find(auth('agent_api')->user()->id);
        $graphValues = $this->agentService->getGraphValues($agent);
        return $graphValues;
    }

    public function showRevenuesWeekly(Request $request, Response $response)
    {
        $agent = Agent::find(auth('agent_api')->user()->id);
        return $this->agentService->getAgentRevenuesWeekly($agent);
    }
}
