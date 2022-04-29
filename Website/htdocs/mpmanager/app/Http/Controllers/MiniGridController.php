<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMiniGridRequest;
use App\Http\Requests\UpdateMiniGridRequest;
use App\Http\Resources\ApiResource;
use App\Models\MiniGrid;
use App\Services\MiniGridService;
use Facade\FlareClient\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class MiniGridController extends Controller
{

    public function __construct(private MiniGridService $miniGridService)
    {

    }

    /**
     * List
     *
     * @urlParam data_stream filters the list based on data_stream column
     *
     * @param Request $request
     * @return ApiResource
     */
    public function index(Request $request): ApiResource
    {
        return ApiResource::make($this->miniGridService->getMiniGrids($request->get('data_stream')));
    }

    /**
     * Detail
     *
     * @bodyParam id int required
     *
     * @param int $id
     *
     * @param Request $request
     *
     * @return ApiResource
     */
    public function show($miniGridId, Request $request): ApiResource
    {
        $relation = $request->get('relation');

        if ((int)$relation === 1) {

            return ApiResource::make($this->miniGridService->getByIdWithLocation($miniGridId));
        } else {

            return ApiResource::make($this->miniGridService->getById($miniGridId));
        }
    }

    public function store(StoreMiniGridRequest $request): ApiResource
    {
        return ApiResource::make($this->miniGridService->create($request->only('cluster_id', 'name')));
    }

    /**
     * Update
     *
     * @bodyParam name string The name of the MiniGrid.
     * @bodyParam data_stream int If the data_stream is enabled or not.
     *
     * @param MiniGrid $miniGrid
     * @param UpdateMiniGridRequest $request
     * @return ApiResource
     */
    public function update($miniGridId, UpdateMiniGridRequest $request): ApiResource
    {
        $miniGrid = $this->miniGridService->getById($miniGridId);
        $this->miniGridService->update($miniGrid, $request->only(['name', 'data_stream']));
        return ApiResource::make($this->miniGridService->getById($miniGridId));
    }
}
