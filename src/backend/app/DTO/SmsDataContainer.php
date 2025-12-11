<?php

namespace App\DTO;

use App\Models\Address\Address;
use App\Models\Person\Person;

/**
 * Data container for SMS list data.
 * Transforms grouped SMS query results into a structured format.
 */
class SmsDataContainer {
    /**
     * @param string              $receiver
     * @param int                 $total
     * @param Address|null        $address
     * @param array<string,mixed> $owner
     */
    public function __construct(
        public string $receiver,
        public int $total,
        public ?Address $address = null,
        public array $owner = [],
    ) {}

    /**
     * Create from grouped SMS query result.
     *
     * @param object $smsData
     */
    public static function fromQuery(object $smsData): self {
        $address = $smsData->address ?? null;
        $owner = [];

        if ($address instanceof Address && $address->owner instanceof Person) {
            $owner = [
                'name' => $address->owner->name,
                'surname' => $address->owner->surname,
                'title' => $address->owner->title,
                'is_active' => $address->owner->is_active,
            ];
        }

        return new self(
            receiver: $smsData->receiver,
            total: (int) $smsData->total,
            address: $address,
            owner: $owner,
        );
    }

    /**
     * Convert to array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array {
        return [
            'receiver' => $this->receiver,
            'total' => $this->total,
            'address' => $this->owner ? [
                'owner' => $this->owner,
            ] : null,
        ];
    }
}
