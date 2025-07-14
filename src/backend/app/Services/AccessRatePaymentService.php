<?php

namespace App\Services;

use App\Models\AccessRate\AccessRatePayment;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<AccessRatePayment>
 */
class AccessRatePaymentService implements IBaseService {
    public function __construct(
        private AccessRatePayment $accessRatePayment,
    ) {}

    public function getById(int $id): AccessRatePayment {
        return $this->accessRatePayment->newQuery()->find($id);
    }

    /**
     * @param array<string, mixed> $accessRatePaymentData
     */
    public function create(array $accessRatePaymentData): AccessRatePayment {
        return $this->accessRatePayment->newQuery()->create($accessRatePaymentData);
    }

    /**
     * @param array<string, mixed> $accessRatePaymentData
     */
    public function update($accessRatePayment, array $accessRatePaymentData): AccessRatePayment {
        $accessRatePayment->update($accessRatePaymentData);
        $accessRatePayment->fresh();

        return $accessRatePayment;
    }

    public function delete($accessRatePayment): ?bool {
        return $accessRatePayment->delete();
    }

    /**
     * @return Collection<int, AccessRatePayment>|LengthAwarePaginator<AccessRatePayment>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $query = $this->accessRatePayment->newQuery();

        if ($limit) {
            return $query->paginate($limit);
        }

        return $this->accessRatePayment->newQuery()->get();
    }

    public function getAccessRatePaymentByMeter($meter): ?AccessRatePayment {
        return $meter->accessRatePayment()->first();
    }
}
