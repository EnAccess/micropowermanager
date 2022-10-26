<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeterRequest;
use App\Http\Resources\ApiResource;
use App\Services\MeterGeographicalInformationService;
use App\Services\MeterService;
use App\Models\City;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterConsumption;
use App\Models\Meter\MeterToken;
use App\Models\PaymentHistory;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MeterController extends Controller
{
    public function __construct(
        private MeterService $meterService
    ) {
    }

    /**
     * List
     * Lists all used meters with meterType and meterParameters.tariff
     * The response is paginated with 15 results on each page/request
     *
     * @urlParam     page int
     * @urlParam     in_use int to list wether used or all meters
     * @responseFile responses/meters/meters.list.json
     * @param Request $request
     * @return       ApiResource
     */
    public function index(Request $request): ApiResource
    {
        $inUse = $request->input('in_use');
        $limit = $request->input('limit', config('settings.paginate'));

        return ApiResource::make($this->meterService->getAll($limit, $inUse));
    }

    /**
     * Create
     * Stores a new meter
     *
     * @param MeterRequest $request
     * @bodyParam serial_number string required
     * @bodyParam meter_type_id int required
     * @bodyParam manufacturer_id int required
     * @return    mixed
     * @throws    ValidationException
     */
    public function store(MeterRequest $request)
    {
        $meterData = (array)$request->all();

        return ApiResource::make($this->meterService->create($meterData));
    }

    /**
     * Detail
     * Detailed meter with following relations
     * - MeterParameter.tariff
     * - MeterParameter.owner
     * - Meter Type
     * - MeterParameter.connectionType
     * - MeterParameter.connectionGroup
     * - Manufacturer
     *
     * @urlParam serialNumber string
     * @param string $serialNumber
     *
     * @return ApiResource
     *
     * @responseFile responses/meters/meter.detail.json
     */
    public function show(string $serialNumber): ApiResource
    {
        return ApiResource::make($this->meterService->getBySerialNumber($serialNumber));
    }

    /**
     * Search
     * The search term will be searched in following fields
     * - Tariff.name
     * - Serial number
     *
     * @bodyParam term string required
     *
     * @return ApiResource
     *
     * @responseFile responses/meters/meters.search.json
     */
    public function search(): ApiResource
    {
        $term = request('term');
        $paginate = request('paginate') ?? 1;

        return ApiResource::make($this->meterService->search($term, $paginate));
    }

    /**
     * List with all relation
     * The output is neither sorted nor paginated
     * The list contains following relations
     *
     * @urlParam     id The ID of the meter
     * @responseFile responses/meters/meter.with.all.relations.json
     * @param        $id
     * @return       ApiResource
     */
    public function allRelations($meterId): ApiResource
    {
        return ApiResource::make($this->meterService->getMeterWithAllRelations($meterId));
    }

    /**
     * Delete
     * Deletes the meter with its all releations
     *
     * @urlParam meterId. The ID of the meter to be delete
     * @param    $meterId
     * @return   JsonResponse
     */
    public function destroy($meterId): JsonResponse
    {
        $meter = $this->meterService->getById($meterId);
        return response()->json(null, 204);
    }
}
