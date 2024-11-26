<?php

namespace Inensus\ViberMessaging\Services;

use App\Models\Person\Person;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Inensus\ViberMessaging\Models\ViberContact;

/**
 * @implements IBaseService<ViberContact>
 */
class ViberContactService implements IBaseService {
    public function __construct(
        private ViberContact $viberContact,
        private Person $person,
    ) {}

    public function createContact($personId, $viberId) {
        return $this->viberContact->newQuery()->firstOrCreate(['person_id' => $personId], [
            'viber_id' => $viberId,
        ]);
    }

    public function getById(int $id): ViberContact {
        return $this->viberContact->newQuery()->find($id);
    }

    public function create(array $data): ViberContact {
        return $this->viberContact->newQuery()->create($data);
    }

    public function update($model, array $data): ViberContact {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }

    public function getByReceiverPhoneNumber($receiver) {
        return $this->viberContact->newQuery()
            ->whereHas('mpmPerson', function ($q) use ($receiver) {
                $q->whereHas('addresses', static function ($q) use ($receiver) {
                    $q->where('phone', $receiver)->orWhere('phone', ltrim($receiver, '+'));
                });
            })->first();
    }

    public function getByRegisteredMeterSerialNumber($meterSerialNumber) {
        return $this->viberContact->newQuery()->where('registered_meter_serial_number', $meterSerialNumber)->first();
    }

    public function getByViberId($viberId) {
        return $this->viberContact->newQuery()->where('viber_id', $viberId)->first();
    }
}
