<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTargetRequest;
use App\Http\Resources\ApiResource;
use App\Models\City;
use App\Models\Cluster;
use App\Models\MiniGrid;
use App\Models\SubTarget;
use App\Models\Target;
use App\Services\ClusterService;
use App\Services\MiniGridService;
use App\Services\SubTargetService;
use App\Services\TargetService;
use Illuminate\Http\Request;

class TargetController extends Controller
{
    public function __construct(
        private TargetService $targetService,
        private ClusterService $clusterService,
        private MiniGridService $miniGridService,
        private SubTargetService $subTargetService
    ) {
    }

    public function index(Request $request): ApiResource
    {
        $limit = $request->input('limit', 15);

        return ApiResource::make($this->targetService->getAll($limit));
    }

    public function show($targetId): ApiResource
    {
        return  ApiResource::make($this->targetService->getById($targetId));
    }

    public function getSlotsForDate(Request $request): ApiResource
    {
        $date = $request->input('date');
        $lastDayOfMonth = date('Y-m-t', strtotime($date));
        $firstDayOfMonth = date('Y-m-1', strtotime($date));
        $targetData = [$firstDayOfMonth, $lastDayOfMonth];

        return  ApiResource::make($this->targetService->getTakenSlots($targetData));
    }

    public function store(CreateTargetRequest $request): ApiResource
    {
        $targetOwnerId = $request->input('targetId');
        $targetData = [
            'data' => $request->input('data'),
            'period' => $request->input('period'),
            'targetType' => $request->input('targetType'),
        ];

        if ($targetData['targetType'] === "cluster") {
            $targetOwner = $this->clusterService->getById($targetOwnerId);
        } else {
            $targetOwner = $this->miniGridService->getById($targetOwnerId);
        }

        $targetData['owner'] = $targetOwner;
        $target = $this->targetService->create($targetData);
        $subTargetData = [
            'data' => $targetData['data'],
            'targetId' => $target->id,
        ];
        $this->subTargetService->create($subTargetData);

        return ApiResource::make($target);
    }
}
