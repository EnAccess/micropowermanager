<?php

namespace Inensus\CalinSmartMeter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalinSmartResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(Request $request) {
        return parent::toArray($request);
    }
}
