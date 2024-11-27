<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @property int        $id
 * @property string     $name
 * @property string     $surname
 * @property Collection $addresses
 */
class SmsSearchResultResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'display' => $this->name.' '.$this->surname,
            'phone' => $this->addresses->where('is_primary', 1)->first()->phone,
        ];
    }
}
