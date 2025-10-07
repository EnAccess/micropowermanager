<?php

namespace Inensus\ViberMessaging\Services;

use App\Services\Interfaces\IBaseService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Inensus\ViberMessaging\Models\ViberContact;

/**
 * @implements IBaseService<ViberContact>
 */
class ViberContactService implements IBaseService {
    public function __construct(
        private ViberContact $viberContact,
    ) {}

    public function createContact(int $personId, int $viberId): ViberContact {
        return $this->viberContact->newQuery()->firstOrCreate(['person_id' => $personId], [
            'viber_id' => $viberId,
        ]);
    }

    public function getById(int $id): ViberContact {
        return $this->viberContact->newQuery()->find($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): ViberContact {
        return $this->viberContact->newQuery()->create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): ViberContact {
        throw new \Exception('Method update() not yet implemented.');
    }

    /**
     * @param ViberContact $model
     */
    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, ViberContact>
     */
    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }

    public function getByReceiverPhoneNumber(string $receiver): ?ViberContact {
        return $this->viberContact->newQuery()
            ->whereHas('mpmPerson', function ($q) use ($receiver) {
                $q->whereHas('addresses', static function ($q) use ($receiver) {
                    $q->where('phone', $receiver)->orWhere('phone', ltrim($receiver, '+'));
                });
            })->first();
    }

    public function getByRegisteredMeterSerialNumber(string $meterSerialNumber): ?ViberContact {
        return $this->viberContact->newQuery()->where('registered_meter_serial_number', $meterSerialNumber)->first();
    }

    public function getByViberId(int $viberId): ?ViberContact {
        return $this->viberContact->newQuery()->where('viber_id', $viberId)->first();
    }
}
