<?php

namespace App\Http\Controllers;

use App\Events\NewLogEvent;
use App\Http\Requests\MeterRequest;
use App\Http\Requests\UpdateMeterRequest;
use App\Http\Resources\ApiResource;
use App\Lib\VendingProvider;
use App\Lib\VendingProviderRegistry;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Services\DeviceControlService;
use App\Services\MeterService;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MeterController extends Controller {
    public function __construct(
        private MeterService $meterService,
        private DeviceControlService $deviceControlService,
        private VendingProviderRegistry $vendingProviderRegistry,
    ) {}

    /**
     * List meters.
     *
     * Lists all used meters with their meter type.
     * The response is paginated with 15 results on each page/request.
     */
    #[QueryParameter('in_use', description: 'Whether to list only meters in use or all meters.', type: 'int')]
    public function index(Request $request): ApiResource {
        $inUse = $request->input('in_use');
        $limit = $request->input('limit', 15);

        return ApiResource::make($this->meterService->getAll($limit, $inUse));
    }

    /**
     * Create a meter.
     *
     * @return mixed
     *
     * @throws ValidationException
     */
    public function store(MeterRequest $request) {
        $meterData = (array) $request->all();

        return ApiResource::make($this->meterService->create($meterData));
    }

    /**
     * Get meter details.
     *
     * Detailed meter with following relations
     * - Tariff.tariff
     * - Meter Type
     * - Meter.connectionType
     * - Meter.connectionGroup
     * - Manufacturer.
     */
    public function show(string $serialNumber): ApiResource {
        return ApiResource::make($this->meterService->getBySerialNumber($serialNumber));
    }

    /**
     * Search meters.
     *
     * The search term will be searched in following fields
     * - Tariff.name
     * - Serial number.
     */
    public function search(): ApiResource {
        $term = request('term');
        $paginate = request('paginate') ?? 1;

        return ApiResource::make($this->meterService->search($term, $paginate));
    }

    /**
     * Delete a meter.
     *
     * Deletes the meter with all its relations.
     */
    public function destroy(int $meterId): JsonResponse {
        $this->meterService->getById($meterId);

        return response()->json(null, 204);
    }

    public function update(UpdateMeterRequest $request, Meter $meter): ApiResource {
        $creatorId = auth('api')->user()->id;
        $previousDataOfMeter = json_encode($meter->toArray());
        $updatedMeter = $this->meterService->update($meter, $request->validated());
        $updatedDataOfMeter = json_encode($updatedMeter->toArray());
        event(new NewLogEvent([
            'user_id' => $creatorId,
            'affected' => $meter,
            'action' => "Meter infos updated from: $previousDataOfMeter to $updatedDataOfMeter",
        ]));

        if ($updatedMeter->wasChanged('vending_provider') && $updatedMeter->device instanceof Device) {
            $this->deviceControlService->refreshManufacturerMapping($updatedMeter->device);
        }

        return ApiResource::make($updatedMeter);
    }

    /**
     * List the vending providers a meter can be switched to.
     *
     * Only providers that support the meter's manufacturer and whose plugin is
     * active are offered.
     */
    public function vendingProviders(Meter $meter): ApiResource {
        return ApiResource::make(array_map(
            fn (VendingProvider $provider): array => ['key' => $provider->key, 'name' => $provider->name],
            $this->vendingProviderRegistry->availableFor($meter->manufacturer),
        ));
    }

    public function showConnectionTypes(): ApiResource {
        return ApiResource::make($this->meterService->getNumberOfConnectionTypes());
    }
}
