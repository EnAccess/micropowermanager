<?php

namespace App\Observers;

use App\Models\Agent;
use App\Models\AgentAssignedAppliances;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCommission;
use App\Models\AgentSoldAppliance;
use App\Models\AssetPerson;
use App\Models\Person\Person;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;

class AgentSoldApplianceObserver
{
    private $agentBalanceHistory;

    public function __construct(AgentBalanceHistory $agentBalanceHistory)
    {
        $this->agentBalanceHistory = $agentBalanceHistory;
    }

    public function created(AgentSoldAppliance $appliances): void
    {
        $assignedApplianceId = $appliances->agent_assigned_appliance_id;
        $assignedAppliance = AgentAssignedAppliances::with('applianceType')->find($assignedApplianceId);

        $appliance = $assignedAppliance->applianceType()->first();
        $agent = Agent::query()->find($assignedAppliance->agent_id);
        $buyer = Person::query()->find(request()->input('person_id'));
        //create agent transaction
        $agentTransaction = AgentTransaction::query()->create(
            [
            'agent_id' => $agent->id,
            'device_id' => $agent->device_id,
            'status' => 1,
            ]
        );

        $transaction = Transaction::query()->make(
            [
            'amount' => request()->input('down_payment'),
            'sender' => $agent->device_id,
            'message' => '-'
            ]
        );
        $transaction->originalTransaction()->associate($agentTransaction);
        $transaction->save();

        $assetPerson = AssetPerson::make(
            [
                'person_id' => request()->input('person_id'),
                'first_payment_date' => request()->input('first_payment_date'),
                'rate_count' => request()->input('tenure'),
                'total_cost' => $assignedAppliance->cost,
                'down_payment' => request()->input('down_payment'),
                'asset_type_id' => $assignedAppliance->applianceType->id,
            ]
        );
        $assetPerson->creator()->associate($agent);
        $assetPerson->save();

        $soldApplianceDataContainer = app()->makeWith(
            'App\Misc\SoldApplianceDataContainer',
            [
                'assetType' => $appliance,
                'assetPerson' => $assetPerson,
                'transaction' => $transaction
            ]
        );

        event('appliance.sold', $soldApplianceDataContainer);


        $history = AgentBalanceHistory::query()->make(
            [
            'agent_id' => $agent->id,
            'amount' => (-1 * request()->input('down_payment')),
            'transaction_id' => $transaction->id,
            'available_balance' => $agent->balance,
            'due_to_supplier' => $agent->due_to_energy_supplier

            ]
        );

        $history->trigger()->associate($assignedAppliance);
        $history->save();


        //create agent commission
        $commission = AgentCommission::query()->find($agent->agent_commission_id);

        $history = AgentBalanceHistory::query()->make(
            [
            'agent_id' => $agent->id,
            'amount' => ($assignedAppliance->cost * $commission->appliance_commission),
            'transaction_id' => $transaction->id,
            'available_balance' => $agent->commission_revenue,
            'due_to_supplier' => $agent->due_to_energy_supplier
            ]
        );
        $history->trigger()->associate($commission);
        $history->save();
    }
}
