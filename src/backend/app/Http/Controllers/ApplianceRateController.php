<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\ApplianceRate;
use App\Services\ApplianceRateService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApplianceRateController extends Controller {
    public function __construct(
        private ApplianceRateService $applianceRateService,
    ) {}

    public function update(Request $request, ApplianceRate $applianceRate): ApiResource {
        if ($applianceRate->rate_cost !== $applianceRate->remaining) {
            throw ValidationException::withMessages(['rate' => 'Cannot modify a rate that has been paid or partially paid']);
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
