<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\AssetRate;
use App\Models\Person\Person;
use App\Services\ApplianceRateService;
use App\Services\CashTransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetRateController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param AssetRate $assetRate
     * @return ApiResource
     */

    private $cashTransactionService;
    private $applianceRateService;

    public function __construct(
        CashTransactionService $cashTransactionService,
        ApplianceRateService $applianceRateService
    ) {
        $this->cashTransactionService = $cashTransactionService;
        $this->applianceRateService = $applianceRateService;
    }

    public function update(Request $request, AssetRate $applianceRate): ApiResource
    {
        $cost = $request->get('cost');
        $newCost = $request->get('newCost');
        $creatorId = $request->get('admin_id');
        $amount = $newCost - $cost;
        $appliancePerson = $applianceRate->assetPerson;

        // notify log listener
        if ($newCost === 0) {
            $this->applianceRateService
                ->deleteUpdatedApplianceRateIfCostZero($applianceRate, $creatorId, $cost, $newCost);
            $appliancePerson->rate_count -= 1;
        } else {
            $this->applianceRateService->updateApplianceRateCost($applianceRate, $creatorId, $cost, $newCost);
        }
        $appliancePerson->total_cost += $amount;
        $appliancePerson->update();
        $appliancePerson->save();

        return new ApiResource($applianceRate);
    }
}
