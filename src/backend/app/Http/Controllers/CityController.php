<?php

namespace App\Http\Controllers;

use App\Http\Requests\CityRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Http\Resources\ApiResource;
use App\Services\CityGeographicalInformationService;
use App\Services\CityService;
use App\Services\GeographicalInformationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityController extends Controller {
    public function __construct(
        private CityService $cityService,
        private GeographicalInformationService $geographicalInformationService,
        private CityGeographicalInformationService $cityGeographicalInformationService,
    ) {}

    public function index(Request $request): ApiResource {
        $limit = $request->get('limit');

        return ApiResource::make($this->cityService->getAll($limit));
    }

    public function show(int $cityId, Request $request): ApiResource {
        $relation = $request->get('relation');

        if ($relation) {
            return ApiResource::make($this->cityService->getByIdWithRelation($cityId, ['location', 'country']));
        }

        return ApiResource::make($this->cityService->getById($cityId));
    }

    public function update(int $cityId, UpdateCityRequest $request): ApiResource {
        $city = $this->cityService->getById($cityId);

        return ApiResource::make($this->cityService->update($city, $request->validated()));
    }

    public function store(CityRequest $request): ApiResource {
        $data = $request->validationData();
        $city = $this->cityService->create($data);
        $geographicalInformation = $this->geographicalInformationService->make($data);
        $this->cityGeographicalInformationService->setAssigned($geographicalInformation);
        $this->cityGeographicalInformationService->setAssignee($city);
        $this->cityGeographicalInformationService->assign();
        $this->geographicalInformationService->save($geographicalInformation);

        return ApiResource::make($city);
    }

    public function destroy(int $cityId): JsonResponse {
        $city = $this->cityService->getById($cityId);
        $this->cityService->delete($city);

        return response()->json(['message' => 'Village deleted.']);
    }
}
