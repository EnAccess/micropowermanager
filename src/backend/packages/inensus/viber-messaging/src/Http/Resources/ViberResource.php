<?php

namespace Inensus\ViberMessaging\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ViberResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(Request $request) {
        return parent::toArray($request);
    }
}
