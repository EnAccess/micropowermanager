<?php

namespace App\Plugins\Whatsapp\Services;

use App\Plugins\Whatsapp\Models\WhatsappMessage;

class WhatsappMessageService {
    public function __construct(private WhatsappMessage $message) {}

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): WhatsappMessage {
        return $this->message->newQuery()->create($data);
    }
}
