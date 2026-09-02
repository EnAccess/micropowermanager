<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\ApplianceRate;
use App\Services\AppliancePaymentService;
use App\Services\ApplianceRateService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApplianceRateController extends Controller {
    public function __construct(
        private ApplianceRateService $applianceRateService,
        private AppliancePaymentService $appliancePaymentService,
    ) {}

    public function update(Request $request, ApplianceRate $applianceRate): ApiResource {
        if ($applianceRate->rate_cost !== $applianceRate->remaining) {
            throw ValidationException::withMessages(['rate' => 'Cannot modify a rate that has been paid or partially paid']);
        }

        if ($this->appliancePaymentService->isOutstandingDownPaymentRate($applianceRate)) {
            throw ValidationException::withMessages(['rate' => 'The down payment agreed at the sale cannot be modified']);
        }

        $cost = $applianceRate->rate_cost;
        $newCost = $request->integer('newCost');
        $creatorId = $request->integer('admin_id');
        $amount = $newCost - $cost;
        $appliancePerson = $applianceRate->appliancePerson;

        if ($newCost === 0) {
            $this->applianceRateService
                ->deleteUpdatedApplianceRateIfCostZero($applianceRate, $creatorId, $cost, $newCost);
            --$appliancePerson->rate_count;
        } else {
            $this->applianceRateService->updateApplianceRateCost($applianceRate, $creatorId, $cost, $newCost);
        }
        $appliancePerson->total_cost += $amount;
        $appliancePerson->save();

        return new ApiResource($applianceRate);
    }
}
