<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMiniGridRequest;
use App\Http\Requests\UpdateMiniGridRequest;
use App\Http\Resources\ApiResource;
use App\Services\MiniGridService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MiniGridController extends Controller {
    public function __construct(
        private MiniGridService $miniGridService,
    ) {}

    /**
     * List.
     */
    public function index(Request $request): ApiResource {
        $limit = $request->input('per_page');

        return ApiResource::make($this->miniGridService->getAll($limit));
    }

    /**
     * Detail.
     *
     * @bodyParam id int required
     */
    public function show(int $miniGridId, Request $request): ApiResource {
        $relation = $request->input('relation');

        if ((int) $relation === 1) {
            return ApiResource::make($this->miniGridService->getByIdWithLocation($miniGridId));
        }

        return ApiResource::make($this->miniGridService->getById($miniGridId));
    }

    public function store(StoreMiniGridRequest $request): ApiResource {
        return ApiResource::make($this->miniGridService->create($request->validated()));
    }

    public function update(int $miniGridId, UpdateMiniGridRequest $request): ApiResource {
        $miniGrid = $this->miniGridService->getById($miniGridId);

        return ApiResource::make($this->miniGridService->update($miniGrid, $request->validated()));
    }

    public function destroy(int $miniGridId): JsonResponse {
        $miniGrid = $this->miniGridService->getById($miniGridId);
        $this->miniGridService->delete($miniGrid);

        return response()->json(['message' => 'Mini-grid deleted.']);
    }
}
