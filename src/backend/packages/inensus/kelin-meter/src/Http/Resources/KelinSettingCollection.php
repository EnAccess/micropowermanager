<?php

namespace Inensus\KelinMeter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class KelinSettingCollection extends ResourceCollection {
    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request) {
        return [
            'data' => $this->collection,
        ];
    }
}
