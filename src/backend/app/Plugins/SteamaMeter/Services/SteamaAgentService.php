<?php

namespace App\Plugins\SteamaMeter\Services;

use App\Models\Address\Address;
use App\Models\Agent;
use App\Models\AgentCommission;
use App\Models\Person\Person;
use App\Plugins\SteamaMeter\Exceptions\SteamaApiResponseException;
use App\Plugins\SteamaMeter\Helpers\ApiHelpers;
use App\Plugins\SteamaMeter\Http\Clients\SteamaMeterApiClient;
use App\Plugins\SteamaMeter\Models\SteamaAgent;
use App\Plugins\SteamaMeter\Models\SteamaSite;
use App\Plugins\SteamaMeter\Models\SyncStatus;
use App\Services\AddressesService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * @implements ISynchronizeService<SteamaAgent>
 */
class SteamaAgentService implements ISynchronizeService {
    private string $rootUrl = '/agents';

    public function __construct(
        private AgentCommission $agentCommission,
        private SteamaAgent $stmAgent,
        private SteamaMeterApiClient $steamaApi,
        private ApiHelpers $apiHelpers,
        private Agent $agent,
        private Person $person,
        private SteamaSite $site,
        private Address $address,
        private SteamaSyncSettingService $steamaSyncSettingService,
        private StemaSyncActionService $steamaSyncActionService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, SteamaAgent>
     */
    public function getAgents(Request $request): LengthAwarePaginator {
        $perPage = (int) $request->input('per_page', 15);

        return $this->stmAgent->newQuery()->with(['mpmAgent.person.addresses', 'site.mpmMiniGrid'])->paginate($perPage);
    }

    public function getAgentsCount(): int {
        return count($this->agent->newQuery()->get());
    }

    /**
     * This function uses one time on installation of the package.
     */
    public function createSteamaAgentCommission(): AgentCommission {
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

    /**
     * @return LengthAwarePaginator<int, SteamaAgent>
     */
    public function sync(): LengthAwarePaginator {
        $synSetting = $this->steamaSyncSettingService->getSyncSettingsByActionName('Agents');
        $syncAction = $this->steamaSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);
            $syncCheck['data']->filter(fn (array $value): bool => $value['syncStatus'] === SyncStatus::NOT_REGISTERED_YET)->each(function (array $agent) {
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
            $syncCheck['data']->filter(fn (array $value): bool => $value['syncStatus'] === SyncStatus::MODIFIED)->each(function (array $agent) {
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
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function syncCheck(bool $returnData = false): array {
        try {
            $url = $this->rootUrl.'?page=1&page_size=100';
            $result = $this->steamaApi->get($url);
            $agents = $result['results'];
            while ($result['next']) {
                $url = $this->rootUrl.'?'.explode('?', $result['next'])[1];
                $result = $this->steamaApi->get($url);
                foreach ($result['results'] as $agent) {
                    $agents[] = $agent;
                }
            }
        } catch (SteamaApiResponseException $e) {
            if ($returnData) {
                return ['result' => false];
            }
            throw new SteamaApiResponseException($e->getMessage());
        }
        // @phpstan-ignore argument.templateType,argument.templateType
        $agentsCollection = collect($agents);
        $stmAgents = $this->stmAgent->newQuery()->get();
        $agents = $this->agent->newQuery()->get();
        $agentsCollection->transform(function (array $agent) use ($stmAgents, $agents): array {
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
        $agentSyncStatus = $agentsCollection->whereNotIn('syncStatus', [SyncStatus::SYNCED])->count();
        if ($agentSyncStatus) {
            return $returnData ? ['data' => $agentsCollection, 'result' => false] : ['result' => false];
        }

        return $returnData ? ['data' => $agentsCollection, 'result' => true] : ['result' => true];
    }

    /**
     * @param array<string, mixed> $stmAgent
     */
    public function createRelatedAgent(array $stmAgent): Agent {
        $person = $this->person->newQuery()->create([
            'name' => $stmAgent['first_name'],
            'surname' => $stmAgent['last_name'],
            'is_customer' => 0,
        ]);
        $site = $this->site->newQuery()->with('mpmMiniGrid.cities')->where('site_id', $stmAgent['site'])->first();

        $city = $site->mpmMiniGrid->cities->first();

        $addressService = app()->make(AddressesService::class);
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

    /**
     * @param array<string, mixed> $stmAgent
     */
    public function updateRelatedAgent(array $stmAgent, Agent $agent): Agent {
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

    /**
     * @param array<string, mixed> $steamaAgent
     */
    private function steamaAgentHasher(array $steamaAgent): string {
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
