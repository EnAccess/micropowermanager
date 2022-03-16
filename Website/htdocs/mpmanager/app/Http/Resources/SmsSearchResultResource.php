<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SmsSearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'display' => $this->name . ' ' . $this->surname,
            'phone' => $this->addresses->where('is_primary', 1)->first()->phone,
        ];
    }
}
