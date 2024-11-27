<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\Target;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use MPM\Target\TargetAssignable;

class TargetService {
    public function __construct(private Target $target) {}

    public function getById($targetId): Target {
        /** @var Target $model */
        $model = $this->target->newQuery()->with(['subTargets', 'city'])->find($targetId);

        return $model;
    }

    public function create(CarbonImmutable $period, string $targetForType, TargetAssignable $targetOwner): Target {
        /** @var Target $target */
        $target = $this->target->newQuery()->make([
            'target_date' => $period->format('Y-m-d'),
            'type' => $targetForType,
        ]);
        if (!$targetOwner instanceof Model) {
            throw new ValidationException('target owner should be a model');
        }
        $target->owner()->associate($targetOwner);
        $target->save();

        return $target;
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->target->newQuery()->with(['owner', 'subTargets.connectionType'])->orderBy(
                'target_date',
                'desc'
            )->paginate($limit);
        }

        return $this->target->newQuery()->with(['owner', 'subTargets.connectionType'])->orderBy(
            'target_date',
            'desc'
        )->get();
    }

    public function getTakenSlots($targetDate): Collection {
        return $this->target->newQuery()->whereBetween('target_date', $targetDate)->get();
    }
}
