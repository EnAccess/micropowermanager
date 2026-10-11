<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<WhatsappMessage>
 */
class WhatsappMessageService implements IBaseService {
    public function __construct(
        private WhatsappMessage $whatsappMessage,
    ) {}

    public function getById(int $id): WhatsappMessage {
        return $this->whatsappMessage->newQuery()->findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): WhatsappMessage {
        return $this->whatsappMessage->newQuery()->create($data);
    }

    public function update($model, array $data): WhatsappMessage {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, WhatsappMessage>
     */
    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}
