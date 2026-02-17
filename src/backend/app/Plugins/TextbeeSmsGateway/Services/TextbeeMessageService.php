<?php

namespace App\Plugins\TextbeeSmsGateway\Services;

use App\Plugins\TextbeeSmsGateway\Models\TextbeeMessage;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<TextbeeMessage>
 */
class TextbeeMessageService implements IBaseService {
    public function __construct(
        private TextbeeMessage $textbeeMessage,
    ) {}

    public function getByMessageId(string $messageId): TextbeeMessage {
        return $this->textbeeMessage->newQuery()->where('message_id', $messageId)->firstOrFail();
    }

    public function getById(int $id): TextbeeMessage {
        return $this->textbeeMessage->newQuery()->where('id', $id)->firstOrFail();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): TextbeeMessage {
        return $this->textbeeMessage->newQuery()->create($data);
    }

    public function update($model, array $data): TextbeeMessage {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, TextbeeMessage>|LengthAwarePaginator<int, TextbeeMessage>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}
