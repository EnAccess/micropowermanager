<?php

namespace Inensus\CalinMeter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalinResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(Request $request) {
        return parent::toArray($request);
    }
}
