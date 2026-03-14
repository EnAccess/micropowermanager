<?php

namespace App\Http\Controllers;

use App\Http\Requests\VillageRequest;
use App\Http\Resources\ApiResource;
use App\Services\VillageGeographicalInformationService;
use App\Services\VillageService;
use App\Services\GeographicalInformationService;
use Illuminate\Http\Request;

class VillageController extends Controller {
    public function __construct(
        private VillageService $villageService,
        private GeographicalInformationService $geographicalInformationService,
        private VillageGeographicalInformationService $villageGeographicalInformationService,
    ) {}

    public function index(Request $request): ApiResource {
        $limit = $request->get('limit');

        return ApiResource::make($this->villageService->getAll($limit));
    }

    public function show(int $villageId, Request $request): ApiResource {
        $relation = $request->get('relation');

        if ($relation) {
            return ApiResource::make($this->villageService->getByIdWithRelation($villageId, ['location', 'country']));
        }

        return ApiResource::make($this->villageService->getById($villageId));
    }

    public function update(int $villageId, VillageRequest $request): ApiResource {
        $village = $this->villageService->getById($villageId);
        $villageData = $request->only(['name', 'mini_grid_id', 'country_id']);

        return ApiResource::make($this->villageService->update($village, $villageData));
    }

    public function store(VillageRequest $request): ApiResource {
        $data = $request->validationData();
        $village = $this->villageService->create($data);
        $geographicalInformation = $this->geographicalInformationService->make($data);
        $this->villageGeographicalInformationService->setAssigned($geographicalInformation);
        $this->villageGeographicalInformationService->setAssignee($village);
        $this->villageGeographicalInformationService->assign();
        $this->geographicalInformationService->save($geographicalInformation);

        return ApiResource::make($village);
    }
}
