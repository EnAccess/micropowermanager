<?php

namespace Inensus\SteamaMeter\Services;

use App\Models\Address\Address;
use App\Models\Agent;
use App\Models\AgentCommission;
use App\Models\Person\Person;
use App\Services\AddressesService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Inensus\SteamaMeter\Exceptions\SteamaApiResponseException;
use Inensus\SteamaMeter\Helpers\ApiHelpers;
use Inensus\SteamaMeter\Http\Clients\SteamaMeterApiClient;
use Inensus\SteamaMeter\Models\SteamaAgent;
use Inensus\SteamaMeter\Models\SteamaSite;
use Inensus\SteamaMeter\Models\SyncStatus;

class SteamaAgentService implements ISynchronizeService {
    private $agentCommission;
    private $agent;
    private $stmAgent;
    private $steamaApi;
    private $apiHelpers;
    private $rootUrl = '/agents';
    private $person;
    private $addressService;
    private $site;
    private $address;
    private $steamaSyncSettingService;
    private $steamaSyncActionService;

    public function __construct(
        AgentCommission $agentCommissionModel,
        SteamaAgent $steamaAgentModel,
        SteamaMeterApiClient $steamaApi,
        ApiHelpers $apiHelpers,
        Agent $agent,
        Person $person,
        AddressesService $addressService,
        SteamaSite $site,
        Address $address,
        SteamaSyncSettingService $steamaSyncSettingService,
        StemaSyncActionService $steamaSyncActionService,
    ) {
        $this->agentCommission = $agentCommissionModel;
        $this->stmAgent = $steamaAgentModel;
        $this->steamaApi = $steamaApi;
        $this->apiHelpers = $apiHelpers;
        $this->agent = $agent;
        $this->person = $person;
        $this->addressService = $addressService;
        $this->site = $site;
        $this->address = $address;
        $this->steamaSyncSettingService = $steamaSyncSettingService;
        $this->steamaSyncActionService = $steamaSyncActionService;
    }

    public function getAgents($request) {
        $perPage = $request->input('per_page') ?? 15;

        return $this->stmAgent->newQuery()->with(['mpmAgent.person.addresses', 'site.mpmMiniGrid'])->paginate($perPage);
    }

    public function getAgentsCount() {
        return count($this->agent->newQuery()->get());
    }

    /**
     * This function uses one time on installation of the package.
     */
    public function createSteamaAgentCommission() {
        $agentCommission = $this->agentCommission->newQuery()->where('name', 'Steama Agent Comission')->first();
        if (!$agentCommission) {
            $agentCommission = $this->agentCommission->newQuery()->create([
                'name' => 'Steama Agent Comission',
                'energy_commission' => 0,
                'appliance_commission' => 0,
                'risk_balance' => -99999999999,
            ]);
        }

        return $agentCommission;
    }

    public function sync() {
        $synSetting = $this->steamaSyncSettingService->getSyncSettingsByActionName('Agents');
        $syncAction = $this->steamaSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);
            $syncCheck['data']->filter(function ($value) {
                return $value['syncStatus'] === SyncStatus::NOT_REGISTERED_YET;
            })->each(function ($agent) {
                $createdAgent = $this->createRelatedAgent($agent);
                $this->stmAgent->newQuery()->create([
                    'agent_id' => $agent['id'],
                    'mpm_agent_id' => $createdAgent->id,
                    'site_id' => $agent['site'],
                    'is_credit_limited' => $agent['is_credit_limited'],
                    'credit_balance' => $agent['credit_balance'],
                    'hash' => $agent['hash'],
                ]);
            });
            $syncCheck['data']->filter(function ($value) {
                return $value['syncStatus'] === SyncStatus::MODIFIED;
            })->each(function ($agent) {
                $relatedAgent = is_null($agent['relatedAgent']) ?
                    $this->createRelatedAgent($agent) : $this->updateRelatedAgent(
                        $agent,
                        $agent['relatedAgent']
                    );
                $agent['registeredStmAgent']->update([
                    'agent_id' => $agent['id'],
                    'mpm_agent_id' => $relatedAgent->id,
                    'site_id' => $agent['site'],
                    'is_credit_limited' => $agent['is_credit_limited'],
                    'credit_balance' => $agent['credit_balance'],
                    'hash' => $agent['hash'],
                ]);
            });
            $this->steamaSyncActionService->updateSyncAction($syncAction, $synSetting, true);

            return $this->stmAgent->newQuery()->with([
                'mpmAgent.person.addresses',
                'site.mpmMiniGrid',
            ])->paginate(config('steama.paginate'));
        } catch (\Exception $e) {
            $this->steamaSyncActionService->updateSyncAction($syncAction, $synSetting, false);
            Log::critical('Steama agents sync failed.', ['Error :' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function syncCheck($returnData = false) {
        try {
            $url = $this->rootUrl.'?page=1&page_size=100';
            $result = $this->steamaApi->get($url);
            $agents = $result['results'];
            while ($result['next']) {
                $url = $this->rootUrl.'?'.explode('?', $result['next'])[1];
                $result = $this->steamaApi->get($url);
                foreach ($result['results'] as $agent) {
                    array_push($agents, $agent);
                }
            }
        } catch (SteamaApiResponseException $e) {
            if ($returnData) {
                return ['result' => false];
            }
            throw new SteamaApiResponseException($e->getMessage());
        }
        $agentsCollection = collect($agents);
        $stmAgents = $this->stmAgent->newQuery()->get();
        $agents = $this->agent->newQuery()->get();
        $agentsCollection->transform(function ($agent) use ($stmAgents, $agents) {
            $registeredStmAgent = $stmAgents->firstWhere('agent_id', $agent['id']);

            $relatedAgent = null;
            $agentHash = $this->steamaAgentHasher($agent);

            if ($registeredStmAgent) {
                $agent['syncStatus'] = $agentHash === $registeredStmAgent->hash ?
                    SyncStatus::SYNCED : SyncStatus::MODIFIED;
                $relatedAgent = $agents->where('id', $registeredStmAgent->mpm_agent_id)->first();
            } else {
                $agent['syncStatus'] = SyncStatus::NOT_REGISTERED_YET;
            }
            $agent['hash'] = $agentHash;
            $agent['relatedAgent'] = $relatedAgent;
            $agent['registeredStmAgent'] = $registeredStmAgent;

            return $agent;
        });
        $agentSyncStatus = $agentsCollection->whereNotIn('syncStatus', SyncStatus::SYNCED)->count();
        if ($agentSyncStatus) {
            return $returnData ? ['data' => $agentsCollection, 'result' => false] : ['result' => false];
        }

        return $returnData ? ['data' => $agentsCollection, 'result' => true] : ['result' => true];
    }

    public function createRelatedAgent($stmAgent) {
        $person = $this->person->newQuery()->create([
            'name' => $stmAgent['first_name'],
            'surname' => $stmAgent['last_name'],
            'is_customer' => 0,
        ]);
        $site = $this->site->newQuery()->with('mpmMiniGrid.cities')->where('site_id', $stmAgent['site'])->first();

        $city = $site->mpmMiniGrid->cities->first();

        $addressService = App::make(AddressesService::class);
        $addressParams = [
            'city_id' => $city->id,
            'email' => '',
            'phone' => $stmAgent['telephone'],
            'street' => $stmAgent['site_name'],
            'is_primary' => 1,
        ];

        $address = $addressService->instantiate($addressParams);
        $agentCommission = $this->agentCommission->newQuery()->where('name', 'Steama Agent Comission')->first();
        $counter = count($this->stmAgent->newQuery()->get());

        $agent = $this->agent->newQuery()->create([
            'person_id' => $person->id,
            'name' => $person->name,
            'password' => $stmAgent['first_name'].$stmAgent['last_name'],
            'email' => 'StmAgent'.strval($counter + 1).'steama.co',
            'mini_grid_id' => $site->mpmMiniGrid->id,
            'agent_commission_id' => $agentCommission->id,
        ]);
        $addressService->assignAddressToOwner($person, $address);

        return $agent;
    }

    public function updateRelatedAgent($stmAgent, $agent) {
        $relatedPerson = $agent->person;
        $relatedPerson->update([
            'name' => $stmAgent['first_name'],
            'surname' => $stmAgent['last_name'],
        ]);

        $site = $this->site->newQuery()->with('mpmMiniGrid')->where('site_id', $stmAgent['site'])->first();

        $city = $site->mpmMiniGrid->cities->first();

        $personId = $relatedPerson->id;

        $address = $this->address->newQuery()->whereHasMorph(
            'owner',
            [Person::class],
            function ($q) use ($personId) {
                $q->where('id', $personId);
            }
        )->first();

        $address->update([
            'city_id' => $city->id,
            'phone' => $stmAgent['telephone'],
            'street' => $stmAgent['site_name'],
            'is_primary' => 1,
        ]);
        $agent->update([
            'name' => $relatedPerson->name,
            'password' => $stmAgent['first_name'].$stmAgent['last_name'],
            'mini_grid_id' => $site->mpmMiniGrid->id,
        ]);

        return $agent;
    }

    private function steamaAgentHasher($steamaAgent) {
        return $this->apiHelpers->makeHash([
            $steamaAgent['first_name'],
            $steamaAgent['last_name'],
            $steamaAgent['telephone'],
            $steamaAgent['site'],
            $steamaAgent['is_credit_limited'],
            $steamaAgent['credit_balance'],
        ]);
    }
}
