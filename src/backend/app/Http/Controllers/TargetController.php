<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTargetRequest;
use App\Http\Resources\ApiResource;
use App\Services\ClusterService;
use App\Services\MiniGridService;
use App\Services\SubTargetService;
use App\Services\TargetService;
use Illuminate\Http\Request;

class TargetController extends Controller {
    public function __construct(
        private TargetService $targetService,
        private ClusterService $clusterService,
        private MiniGridService $miniGridService,
        private SubTargetService $subTargetService,
    ) {}

    public function index(Request $request): ApiResource {
        $limit = $request->input('limit', 15);

        return ApiResource::make($this->targetService->getAll($limit));
    }

    public function show(int $targetId): ApiResource {
        return ApiResource::make($this->targetService->getById($targetId));
    }

    public function getSlotsForDate(Request $request): ApiResource {
        $date = $request->input('date');
        $lastDayOfMonth = date('Y-m-t', strtotime($date));
        $firstDayOfMonth = date('Y-m-1', strtotime($date));
        $targetData = [$firstDayOfMonth, $lastDayOfMonth];

        return ApiResource::make($this->targetService->getTakenSlots($targetData));
    }

    public function store(CreateTargetRequest $request): ApiResource {
        $targetData = [
            'period' => $request->getPeriod(),
            'targetForType' => $request->getTargetForType(),
        ];

        if ($request->getTargetForType() === 'cluster') {
            $targetOwner = $this->clusterService->getById($request->getTargetForId());
        } else {
            $targetOwner = $this->miniGridService->getById($request->getTargetForId());
        }

        $targetData['owner'] = $targetOwner;
        $target = $this->targetService->create($request->getPeriod(), $request->getTargetForType(), $targetOwner);

        $subTargetData = [
            'data' => $request->getData(),
            'targetId' => $target->id,
        ];
        $this->subTargetService->create($subTargetData);

        return ApiResource::make($target);
    }
}
