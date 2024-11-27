<?php

namespace Inensus\ViberMessaging\Services;

use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Inensus\ViberMessaging\Models\ViberMessage;

/**
 * @implements IBaseService<ViberMessage>
 */
class ViberMessageService implements IBaseService {
    public function __construct(
        private ViberMessage $viberMessage,
    ) {}

    public function getById(int $id): ViberMessage {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function create(array $data): ViberMessage {
        return $this->viberMessage->newQuery()->create($data);
    }

    public function update($model, array $data): ViberMessage {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}
