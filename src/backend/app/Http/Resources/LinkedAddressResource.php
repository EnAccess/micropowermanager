<?php

namespace App\Http\Resources;

use App\Models\Address\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Address
 */
class LinkedAddressResource extends JsonResource {
    /**
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'street' => $this->street,
            'is_primary' => (bool) $this->is_primary,
            'owner_type' => $this->owner_type,
            'owner_name' => $this->ownerName(),
        ];
    }

    /**
     * Owners are people, agents, users and manufacturers, and each names itself
     * differently. Reading attributes keeps this resource out of that hierarchy.
     */
    private function ownerName(): ?string {
        $owner = $this->owner;

        if ($owner === null) {
            return null;
        }

        $name = $owner->getAttribute('name');

        if ($name === null) {
            return $owner->getAttribute('email');
        }

        return trim($name.' '.($owner->getAttribute('surname') ?? ''));
    }
}
