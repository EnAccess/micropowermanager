<?php

namespace App\Http\Middleware;

use App\Exceptions\AgentRiskBalanceExceeded;
use App\Exceptions\DownPaymentBiggerThanAmountException;
use App\Exceptions\DownPaymentNotFoundException;
use App\Exceptions\TransactionAmountNotFoundException;
use App\Models\AgentAssignedAppliances;
use App\Services\AgentAssignedApplianceService;
use App\Services\AgentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class AgentBalanceMiddleware {
    public function __construct(
        private AgentService $agentService,
        private AgentAssignedApplianceService $agentAssignedApplianceService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function handle($request, \Closure $next) {
        $routeName = request()->route()->getName();
        $agent = $this->agentService->getByAuthenticatedUser();
        $commission = $agent->commission()->first();
        $agentBalance = $agent->balance;

        if ($routeName === 'agent-sell-appliance') {
            $assignedApplianceCost = $this->agentAssignedApplianceService->getById($request->input('agent_assigned_appliance_id'));
            $downPayment = $request->input('down_payment');
            throw_unless($assignedApplianceCost instanceof AgentAssignedAppliances, new ModelNotFoundException('Assigned Appliance not found'));

            throw_unless(isset($downPayment), new DownPaymentNotFoundException('DownPayment not found'));

            $agentBalance -= $downPayment;

            throw_if($assignedApplianceCost->cost < $request->input('down_payment'), new DownPaymentBiggerThanAmountException('Down payment is bigger than amount'));
        }
        if ($routeName === 'agent-transaction') {
            if ($transactionAmount = $request->input('amount')) {
                $agentBalance -= $transactionAmount;
            } else {
                throw new TransactionAmountNotFoundException('Transaction amount not found');
            }
        }

        throw_if($agentBalance < $commission->risk_balance, new AgentRiskBalanceExceeded('Risk balance exceeded'));

        return $next($request);
    }
}
