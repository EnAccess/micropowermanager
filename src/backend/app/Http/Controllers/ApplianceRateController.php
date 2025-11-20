<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\ApplianceRate;
use App\Services\ApplianceRateService;
use Illuminate\Http\Request;

class ApplianceRateController extends Controller {
    public function __construct(
        /**
         * Update the specified resource in storage.
         *
         * @param Request   $request
         * @param ApplianceRate $applianceRate
         *
         * @return ApiResource
         */
        private ApplianceRateService $applianceRateService,
    ) {}

    public function update(Request $request, ApplianceRate $applianceRate): ApiResource {
        $cost = $request->get('cost');
        $newCost = $request->get('newCost');
        $creatorId = $request->get('admin_id');
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
