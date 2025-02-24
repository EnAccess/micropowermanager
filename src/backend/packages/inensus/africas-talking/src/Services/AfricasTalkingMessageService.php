<?php

namespace Inensus\AfricasTalking\Services;

use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Inensus\AfricasTalking\Models\AfricasTalkingMessage;

class AfricasTalkingMessageService implements IBaseService {
    public function __construct(
        private AfricasTalkingMessage $africasTalkingMessage,
    ) {}

    public function getByMessageId(string $messageId): AfricasTalkingMessage {
        return $this->africasTalkingMessage->newQuery()->where('message_id', $messageId)->firstOrFail();
    }

    public function getById(int $id): AfricasTalkingMessage {
        return $this->africasTalkingMessage->newQuery()->where('id', $id)->firstOrFail();
    }

    public function create(array $data): AfricasTalkingMessage {
        return $this->africasTalkingMessage->newQuery()->create($data);
    }

    public function update($model, array $data): AfricasTalkingMessage {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}
