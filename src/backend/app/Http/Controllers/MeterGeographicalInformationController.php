<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Meter\Meter;
use App\Services\CityService;
use App\Services\MeterGeographicalInformationService;
use App\Services\MeterService;
use App\Services\PersonMeterService;
use Illuminate\Http\Request;

class MeterGeographicalInformationController extends Controller {
    public function __construct(
        private MeterGeographicalInformationService $meterGeographicalInformationService,
        private PersonMeterService $personMeterService,
        private CityService $cityService,
        private MeterService $meterService,
    ) {}

    /**
     * List meters with geo and access rate.
     *
     * A list of meters with their positions and access rate payments.
     * The list is not paginated.
     */
    public function index(?int $miniGridId = null): ApiResource {
        $cityIds = $miniGridId ? $this->cityService->getCityIdsByMiniGridId($miniGridId) : [];
        // we can get city id only by address
        if ($miniGridId === null) {
            $meters = $this->meterService->getUsedMetersGeoWithAccessRatePayments();
        } else {
            $meters = $this->meterService->getUsedMetersGeoWithAccessRatePaymentsInCities($cityIds);
        }

        return ApiResource::make($meters);
    }

    /**
     * Get person meters with geo coordinates.
     *
     * Person details with his/her owned meter(s) and the geo coordinates where each meter is placed
     * - Meters
     *   - Meter coordinates
     * A list of meters which belong to that given person
     * The list is neither sorted nor paginated.
     */
    public function show(int $personId): ApiResource {
        return ApiResource::make($this->personMeterService->getPersonMetersGeographicalInformation($personId));
    }

    /**
     * Update meter geo coordinates.
     */
    public function update(Request $request): ApiResource {
        $meters = $request->all();

        return ApiResource::make($this->meterGeographicalInformationService->updateGeographicalInformation($meters));
    }
}
