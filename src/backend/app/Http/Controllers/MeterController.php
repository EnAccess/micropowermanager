<?php

namespace App\Http\Controllers;

use App\Events\NewLogEvent;
use App\Http\Requests\MeterRequest;
use App\Http\Requests\UpdateMeterRequest;
use App\Http\Resources\ApiResource;
use App\Models\Meter\Meter;
use App\Services\MeterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MeterController extends Controller {
    public function __construct(
        private MeterService $meterService,
    ) {}

    /**
     * List
     * Lists all used meters with meterType
     * The response is paginated with 15 results on each page/request.
     *
     * @urlParam     page int
     * @urlParam     in_use int to list wether used or all meters
     *
     * @responseFile responses/meters/meters.list.json
     *
     * @param Request $request
     *
     * @return ApiResource
     */
    public function index(Request $request): ApiResource {
        $inUse = $request->input('in_use');
        $limit = $request->input('limit', config('settings.paginate'));

        return ApiResource::make($this->meterService->getAll($limit, $inUse));
    }

    /**
     * Create
     * Stores a new meter.
     *
     * @param MeterRequest $request
     *
     * @bodyParam serial_number string required
     * @bodyParam meter_type_id int required
     * @bodyParam manufacturer_id int required
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
     * Detail
     * Detailed meter with following relations
     * - MeterTariff.tariff
     * - Meter Type
     * - Meter.connectionType
     * - Meter.connectionGroup
     * - Manufacturer.
     *
     * @urlParam serialNumber string
     *
     * @param string $serialNumber
     *
     * @return ApiResource
     *
     * @responseFile responses/meters/meter.detail.json
     */
    public function show(string $serialNumber): ApiResource {
        return ApiResource::make($this->meterService->getBySerialNumber($serialNumber));
    }

    /**
     * Search
     * The search term will be searched in following fields
     * - Tariff.name
     * - Serial number.
     *
     * @bodyParam term string required
     *
     * @return ApiResource
     *
     * @responseFile responses/meters/meters.search.json
     */
    public function search(): ApiResource {
        $term = request('term');
        $paginate = request('paginate') ?? 1;

        return ApiResource::make($this->meterService->search($term, $paginate));
    }

    /**
     * Delete
     * Deletes the meter with its all releations.
     *
     * @urlParam meterId. The ID of the meter to be delete
     *
     * @param $meterId
     *
     * @return JsonResponse
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

        return ApiResource::make($updatedMeter);
    }

    public function showConnectionTypes(): ApiResource {
        return ApiResource::make($this->meterService->getNumberOfConnectionTypes());
    }
}
