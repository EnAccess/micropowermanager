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
            if (!$assignedApplianceCost instanceof AgentAssignedAppliances) {
                throw new ModelNotFoundException('Assigned Appliance not found');
            }

            if (!isset($downPayment)) {
                throw new DownPaymentNotFoundException('DownPayment not found');
            }

            $agentBalance += $downPayment;

            if ($assignedApplianceCost->cost < $request->input('down_payment')) {
                throw new DownPaymentBiggerThanAmountException('Down payment is bigger than amount');
            }
        }
        if (in_array($routeName, ['agent-transaction', 'agent-app-transaction'], true)) {
            if ($transactionAmount = $request->input('amount')) {
                $agentBalance += $transactionAmount;
            } else {
                throw new TransactionAmountNotFoundException('Transaction amount not found');
            }
        }

        // risk_balance is the ceiling of company money the agent may hold before
        // they must transfer it back; once the sale would push them over, block it.
        if ($agentBalance > $commission->risk_balance) {
            throw new AgentRiskBalanceExceeded('Risk balance exceeded');
        }

        return $next($request);
    }
}
