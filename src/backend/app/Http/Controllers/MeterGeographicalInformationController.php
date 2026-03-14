<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Meter\Meter;
use App\Services\VillageService;
use App\Services\MeterGeographicalInformationService;
use App\Services\MeterService;
use App\Services\PersonMeterService;
use Illuminate\Http\Request;

class MeterGeographicalInformationController extends Controller {
    public function __construct(
        private MeterGeographicalInformationService $meterGeographicalInformationService,
        private PersonMeterService $personMeterService,
        private VillageService $villageService,
        private MeterService $meterService,
    ) {}

    /**
     * List with geo and access rate
     * A list of meters with their positions and access rate payments
     * The list is not paginated.
     *
     * @urlParam mini_grid_id int
     *
     * @responseFile responses/meters/meters.geo.list.json
     */
    public function index(?int $miniGridId = null): ApiResource {
        $villageIds = $miniGridId ? $this->villageService->getVillageIdsByMiniGridId($miniGridId) : [];
        // we can get village id only by address
        if ($miniGridId === null) {
            $meters = $this->meterService->getUsedMetersGeoWithAccessRatePayments();
        } else {
            $meters = $this->meterService->getUsedMetersGeoWithAccessRatePaymentsInVillages($villageIds);
        }

        return ApiResource::make($meters);
    }

    /**
     * @group    People
     * Person with Meters & geo
     * Person details with his/her owned meter(s) and the geo coordinates where each meter is placed
     * - Meters
     *   - Meter coordinates
     * A list of meters which belong to that given person
     * The list is wether sorted or paginated
     *
     * @urlParam person required The ID of the person
     *
     * @responseFile responses/people/person.meter.list.json
     */
    public function show(int $personId): ApiResource {
        return ApiResource::make($this->personMeterService->getPersonMetersGeographicalInformation($personId));
    }

    /**
     * Update
     * Updates the geo coordinates of the meter.
     *
     * @urlParam  meter int
     *
     * @bodyParam points string. Comma seperated latitude and longitude. Example 1,2
     */
    public function update(Request $request): ApiResource {
        $meters = $request->all();

        return ApiResource::make($this->meterGeographicalInformationService->updateGeographicalInformation($meters));
    }
}
