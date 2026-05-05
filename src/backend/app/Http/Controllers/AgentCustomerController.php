<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignMeterToCustomerRequest;
use App\Http\Requests\CreateAgentCustomerRequest;
use App\Http\Resources\ApiResource;
use App\Models\Person\Person;
use App\Services\AgentCustomerService;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AgentCustomerController extends Controller {
    public function __construct(
        private AgentCustomerService $agentCustomerService,
        private AgentService $agentService,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return ApiResource
     */
    public function index(Request $request) {
        $perPage = $request->integer('per_page', 15);
        $agent = $this->agentService->getByAuthenticatedUser();

        return ApiResource::make($this->agentCustomerService->list($agent, $perPage));
    }

    public function show(int $customerId): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();

        return ApiResource::make($this->agentCustomerService->findForAgent($agent, $customerId));
    }

    public function search(Request $request): ApiResource {
        $term = $request->input('term');
        $limit = $request->input('paginate', 15);
        $agent = $this->agentService->getByAuthenticatedUser();

        return ApiResource::make($this->agentCustomerService->search($term, $limit, $agent));
    }

    public function store(CreateAgentCustomerRequest $request): JsonResponse {
        $agent = $this->agentService->getByAuthenticatedUser();

        try {
            DB::connection('tenant')->beginTransaction();
            $person = $this->agentCustomerService->register($agent, $request);
            DB::connection('tenant')->commit();

            return ApiResource::make($person)->response()->setStatusCode(201);
        } catch (ValidationException $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::critical('Error while an agent was registering a customer', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function storeMeter(AssignMeterToCustomerRequest $request, int $customerId): JsonResponse {
        $agent = $this->agentService->getByAuthenticatedUser();
        $customer = Person::query()->where('is_customer', 1)->findOrFail($customerId);

        try {
            DB::connection('tenant')->beginTransaction();
            $meter = $this->agentCustomerService->assignMeter($agent, $customer, $request);
            DB::connection('tenant')->commit();

            return ApiResource::make($meter)->response()->setStatusCode(201);
        } catch (ValidationException $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::critical('Error while an agent was assigning a meter to a customer', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}
