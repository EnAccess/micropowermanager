<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMiniGridRequest;
use App\Http\Requests\UpdateMiniGridRequest;
use App\Http\Resources\ApiResource;
use App\Models\MiniGrid;
use App\Services\GeographicalInformationService;
use App\Services\MiniGridGeographicalInformationService;
use App\Services\MiniGridService;
use Illuminate\Http\Request;

class MiniGridController extends Controller {
    public function __construct(
        private MiniGridService $miniGridService,
        private GeographicalInformationService $geographicalInformationService,
        private MiniGridGeographicalInformationService $miniGridGeographicalInformationService,
    ) {}

    /**
     * List.
     *
     * @param Request $request
     *
     * @return ApiResource
     */
    public function index(Request $request): ApiResource {
        $limit = $request->input('per_page');

        return ApiResource::make($this->miniGridService->getAll($limit));
    }

    /**
     * Detail.
     *
     * @bodyParam id int required
     *
     * @param int     $miniGridId
     * @param Request $request
     *
     * @return ApiResource
     */
    public function show($miniGridId, Request $request): ApiResource {
        $relation = $request->get('relation');

        if ((int) $relation === 1) {
            return ApiResource::make($this->miniGridService->getByIdWithLocation($miniGridId));
        } else {
            return ApiResource::make($this->miniGridService->getById($miniGridId));
        }
    }

    public function store(StoreMiniGridRequest $request): ApiResource {
        $data = $request->validationData();
        $miniGrid = $this->miniGridService->create($request->only(['name', 'cluster_id']));
        $geographicalInformation = $this->geographicalInformationService->make(['points' => $data['geo_data']]);
        $this->miniGridGeographicalInformationService->setAssigned($geographicalInformation);
        $this->miniGridGeographicalInformationService->setAssignee($miniGrid);
        $this->miniGridGeographicalInformationService->assign();
        $this->geographicalInformationService->save($geographicalInformation);

        return ApiResource::make($miniGrid);
    }

    /**
     * Update.
     *
     * @bodyParam name string The name of the MiniGrid.
     *
     * @param int                   $miniGridId
     * @param UpdateMiniGridRequest $request
     *
     * @return ApiResource
     */
    public function update($miniGridId, UpdateMiniGridRequest $request): ApiResource {
        $miniGrid = $this->miniGridService->getById($miniGridId);

        return ApiResource::make($this->miniGridService->getById($miniGridId));
    }
}
