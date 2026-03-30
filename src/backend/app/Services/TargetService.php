<?php

namespace App\Services;

use App\Models\Interfaces\ITargetAssignable;
use App\Models\Target;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TargetService {
    public function __construct(private Target $target) {}

    public function getById(int $targetId): Target {
        return $this->target->newQuery()->with(['subTargets', 'city'])->find($targetId);
    }

    public function create(CarbonImmutable $period, string $targetForType, ITargetAssignable $targetOwner): Target {
        $target = $this->target->newQuery()->make([
            'target_date' => $period->format('Y-m-d'),
            'type' => $targetForType,
        ]);
        if (!$targetOwner instanceof Model) {
            throw ValidationException::withMessages(['target_owner' => 'target owner should be a model']);
        }
        $target->owner()->associate($targetOwner);
        $target->save();

        return $target;
    }

    /**
     * @return Collection<int, Target>|LengthAwarePaginator<int, Target>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->target->newQuery()->with(['owner', 'subTargets.connectionType'])->latest(
                'target_date'
            )->paginate($limit);
        }

        return $this->target->newQuery()->with(['owner', 'subTargets.connectionType'])->latest(
            'target_date'
        )->get();
    }

    /**
     * @param array<string> $targetDate
     *
     * @return Collection<int, Target>
     */
    public function getTakenSlots(array $targetDate): Collection {
        return $this->target->newQuery()->whereBetween('target_date', $targetDate)->get();
    }
}
