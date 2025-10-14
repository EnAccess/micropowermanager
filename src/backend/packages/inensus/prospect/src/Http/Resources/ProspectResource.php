<?php

namespace Inensus\Prospect\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProspectResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(\Illuminate\Http\Request $request) {
        return parent::toArray($request);
    }
}
