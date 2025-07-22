<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentRequest;
use App\Http\Resources\ApiResource;
use App\Models\CompanyDatabase;
use App\Services\AddressesService;
use App\Services\AgentService;
use App\Services\CountryService;
use App\Services\DatabaseProxyService;
use App\Services\PersonAddressService;
use App\Services\PersonService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AgentWebController extends Controller {
    public function __construct(
        private AgentService $agentService,
        private AddressesService $addressService,
        private PersonService $personService,
        private PersonAddressService $personAddressService,
        private CountryService $countryService,
        private DatabaseProxyService $databaseProxyService,
    ) {}

    public function index(Request $request): ApiResource {
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentService->getAll($limit));
    }

    public function show(int $agentId, Request $request): ApiResource {
        return ApiResource::make($this->agentService->getById($agentId));
    }

    public function store(CreateAgentRequest $request): ApiResource {
        $addressData = $this->addressService->createAddressDataFromRequest($request);
        $personData = $this->personService->createPersonDataFromRequest($request);
        $country = $this->countryService->getByCode($request->get('nationality'));
        $agentData = [
            'password' => $request['password'],
            'email' => $request['email'],
            'mini_grid_id' => $request['city_id'],
            'agent_commission_id' => $request['agent_commission_id'],
            'mobile_device_id' => '-',
            'fire_base_token' => '-',
            'connection' => ' ', // TODO:  solve this.  //auth('api')->user()->company->database->database_name
        ];
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $companyId = $guard->payload()->get('companyId');
        $companyDatabase = CompanyDatabase::query()->where('company_id', $companyId)->firstOrFail();
        $databaseProxyData = [
            'email' => $request['email'],
            'fk_company_id' => $companyId,
            'fk_company_database_id' => $companyDatabase->getId(),
        ];
        $this->databaseProxyService->create($databaseProxyData);

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

    public function update(int $agentId, Request $request): ApiResource {
        $agent = $this->agentService->getById($agentId);
        $agentData = $request->all();

        return ApiResource::make($this->agentService->update($agent, $agentData));
    }

    public function destroy(int $agentId, Request $request): ApiResource {
        $agent = $this->agentService->getById($agentId);

        $deleted = $this->agentService->delete($agent);

        return ApiResource::make([
            'message' => $deleted ? 'Agent deleted successfully' : 'Failed to delete agent',
            'status_code' => $deleted ? 200 : 400,
        ]);
    }

    public function search(Request $request): ApiResource {
        $term = $request->input('term');
        $paginate = $request->input('paginate', 1);

        return ApiResource::make($this->agentService->searchAgent($term, $paginate));
    }

    /**
     * @return Response
     */
    public function resetPassword(Request $request, Response $response) {
        $responseMessage = $this->agentService->resetPassword($request->input('email'));

        if ($responseMessage === 'Invalid email.') {
            return $response->setStatusCode(422)->setContent(
                [
                    'data' => [
                        'message' => $responseMessage,
                        'status_code' => 400,
                    ],
                ]
            );
        }

        return $response->setStatusCode(200)->setContent(
            [
                'data' => [
                    'message' => $responseMessage,
                    'status_code' => 200,
                ],
            ]
        );
    }
}
