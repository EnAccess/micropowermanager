<?php

namespace Inensus\ChintMeter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChintResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request) {
        return parent::toArray($request);
    }
}
