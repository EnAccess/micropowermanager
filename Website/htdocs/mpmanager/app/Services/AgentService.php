<?php

namespace App\Services;

use App\Helpers\PasswordGenerator;
use App\Models\Agent;
use App\Services\CountryService;
use App\Services\PeriodService;
use App\Models\Address\Address;

use App\Models\AgentBalanceHistory;
use App\Models\AgentReceipt;
use App\Models\Country;
use App\Models\Person\Person;
use Complex\Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\This;

class AgentService extends BaseService implements IBaseService
{

    public function __construct(
        private Agent $agent,
        private PeriodService $periodService
    ) {

        parent::__construct([$agent]);
    }

    /**
     * @param string $email
     * @return int|string
     */
    public function resetPassword(string $email)
    {
        try {
            $newPassword = PasswordGenerator::generatePassword();
        } catch (Exception $exception) {
            $newPassword = time();
        }
        try {
            $agent = $this->agent->newQuery()->where('email', $email)->firstOrFail();
        } catch (ModelNotFoundException $x) {
            $message = 'Invalid email.';
            return $message;
        }

        $agent->password = $newPassword;
        $agent->update();
        $agent->fresh();
        return $newPassword;
    }

    public function updateDevice($agent, $deviceId): void
    {
        $agent->device_id = $deviceId;
        $agent->update();
        $agent->fresh();
    }

    public function setFirebaseToken($agent, $firebaseToken): void
    {
        $agent->fire_base_token = $firebaseToken;
        $agent->update();
        $agent->fresh();
    }

    public function getAgentBalance($agent)
    {
        return $agent->balance;
    }

    public function getLastReceiptDate($agent)
    {
        $lastReceiptDate = AgentReceipt::query()->where('agent_id', $agent->id)->get()->last();
        if ($lastReceiptDate) {
            return $lastReceiptDate->created_at;
        }
        return $agent->created_at;
    }

    public function getTransactionAverage($agent)
    {
        $lastReceipt = AgentReceipt::query()->where('id', $agent->id)->get()->last();
        if ($lastReceipt) {
            $averageTransactionAmounts = AgentBalanceHistory::query()
                ->where('agent_id', $agent->id)
                ->where('trigger_type', 'agent_transaction')
                ->where('created_at', '>', $lastReceipt->created_at)
                ->get()
                ->avg('amount');
        } else {
            $averageTransactionAmounts = AgentBalanceHistory::query()
                ->where('agent_id', $agent->id)
                ->where('trigger_type', 'agent_transaction')
                ->get()
                ->avg('amount');
        }
        return -1 * $averageTransactionAmounts;
    }

    /**
     * @param Request|array|string $searchTerm
     * @param Request|array|int|string $paginate
     *
     * @return LengthAwarePaginator|Builder[]|Collection
     */
    public function searchAgent($searchTerm, $paginate)
    {
        if ($paginate === 1) {
            return $this->agent->newQuery()->with('miniGrid')->WhereHas(
                'miniGrid',
                function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', '%' . $searchTerm . '%');
                }
            )->orWhere('name', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('email', 'LIKE', '%' . $searchTerm . '%')->paginate(15);
        }

        return $this->agent->newQuery()->with('miniGrid')->WhereHas(
            'miniGrid',
            function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%');
            }
        )->orWhere('name', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('email', 'LIKE', '%' . $searchTerm . '%')->get();
    }

    /**
     * @return array|false|string
     */
    public function getGraphValues($agent)
    {
        $periodDate = $this->getLastReceiptDate($agent);
        $period = $this->getPeriod($agent, $periodDate);
        $history = AgentBalanceHistory::query()
            ->selectRaw('DATE_FORMAT(created_at,\'%Y-%m-%d\') as date,id,trigger_Type,amount,' .
                'available_balance,due_to_supplier')
            ->where('agent_id', $agent->id)
            ->where('created_at', '>=', $periodDate)
            ->groupBy(DB::raw('DATE_FORMAT(created_at,\'%Y-%m-%d\'),date,id,trigger_Type,amount,' .
                'available_balance,due_to_supplier'))->get();

        if (count($history) === 1 && $history[0]->trigger_Type === 'agent_receipt') {
            $period[$history[0]->date]['balance'] = -1 * ($history[0]->due_to_supplier - $history[0]->amount);
            $period[$history[0]->date]['due'] = $history[0]->due_to_supplier - $history[0]->amount;
        } elseif (count($history) === 0) {
            return json_encode(json_decode("{}", true));
        } else {
            foreach ($period as $key => $value) {
                foreach ($history as $h) {
                    if ($key === $h->date) {
                        $lastRow = $history->where('trigger_Type', '!=', 'agent_commission')
                            ->where('trigger_Type', '!=', 'agent_receipt')
                            ->where(
                                'date',
                                '=',
                                $h->date
                            )->last();

                        $lastComissionRow = $history->where('trigger_Type', '=', 'agent_commission')
                            ->where('trigger_Type', '!=', 'agent_receipt')
                            ->where(
                                'date',
                                '=',
                                $h->date
                            )->last();
                        $period[$key]['balance'] = $lastRow !== null ?
                            $lastRow->amount + $lastRow->available_balance : null;
                        $period[$key]['due'] = $lastRow !== null ? ((-1 * $lastRow->amount) + $lastRow->due_to_supplier)
                            - (1 * $lastComissionRow->amount) : null;
                    }
                }
            }
        }
        return $period;
    }

    /**
     * @return int[][]
     */
    public function getPeriod($agent, $date): array
    {
        $days = AgentBalanceHistory::query()->selectRaw('DATE_FORMAT(created_at,\'%Y-%m-%d\') as day')
            ->where('agent_id', $agent->id)
            ->where(
                'created_at',
                '>=',
                $date
            )->groupBy(DB::raw('DATE_FORMAT(created_at,\'%Y-%m-%d\')'))->get();
        $period = array();
        foreach ($days as $key => $item) {
            $period[$item->day] = [
                'balance' => 0,
                'due' => 0
            ];
        }
        return $period;
    }

    public function getAgentRevenuesWeekly($agent): array
    {
        $startDate = date("Y-m-d", strtotime("-3 months"));
        $endDate = date("Y-m-d");
        $Revenues = AgentBalanceHistory::query()
            ->selectRaw('DATE_FORMAT(created_at,\'%Y-%u\') as period, SUM(amount) as revenue')
            ->where('trigger_type', 'agent_commission')
            ->groupBy(DB::raw('DATE_FORMAT(created_at,\'%Y-%u\')'))
            ->get();

        $p = $this->periodService->generatePeriodicList($startDate, $endDate, 'weekly', ['revenue' => 0]);
        foreach ($Revenues as $rIndex => $revenue) {
            $p[$revenue->period]['revenue'] = $revenue->revenue;
        }
        return $p;
    }

    public function getById($id)
    {
        return $this->agent->newQuery()
            ->with(['person', 'person.addresses', 'miniGrid', 'commission'])
            ->where('id', $id)->firstOrFail();
    }

    public function delete($agent)
    {
       return $agent->delete();
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->agent->newQuery()
                ->with(['person', 'person.addresses', 'miniGrid', 'commission'])
                ->paginate($limit);
        }

        return $this->agent->newQuery()
            ->with(['person.addresses', 'miniGrid'])
            ->get();
    }

    public function create(
        $agentData,
        $addressData = null,
        $personData = null,
        $country = null,
        $addressService = null,
        $countryService = null,
        $personService = null,
        $personAddressService = null,
    )
    {
        $person = $personService->create($personData);

        if ($country !== null) {
            $person = $personService->addCitizenship($person, $country);
        }

        $agentData['person_id'] = $person->id;
        $agentData['name'] = $person->name;
        $address = $addressService->make($addressData);
        $personAddressService->setAssigner($person);
        $personAddressService->setAssigned($address);
        $personAddressService->assign();
        $addressService->save($address);

        return $this->agent->newQuery()->create($agentData);
    }

    /**
     * @param $agent
     * @param $data
     * @return Model|Builder
     */
    public function update($agent, $agentData, $personService = null)
    {

        $person = $personService->getById($agentData['personId']);
        $personData = [
            'name'=>$agentData['name'],
            'surname'=>$agentData['surname'],
            'sex'=> $agentData['gender'],
            'birth_date'=>$agentData['birthday']
        ];
        $person = $personService->update($person,$personData);
        $address = $person->addresses()->where('is_primary',1)->first();
        $address->phone = $agentData['phone'];
        $address->update();
        $agent->name = $agentData['name'];
        $agent->agent_commission_id = $agentData['commissionTypeId'];
        $agent->update();

        return $this->agent->with(['person', 'person.addresses', 'miniGrid', 'commission'])
            ->where('id', $agent->id)->first();
    }

}
