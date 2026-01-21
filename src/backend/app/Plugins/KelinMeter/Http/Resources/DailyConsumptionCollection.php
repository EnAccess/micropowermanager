<?php

namespace App\Plugins\KelinMeter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class DailyConsumptionCollection extends ResourceCollection {
    /**
     * Transform the resource collection into an array.
     *
     * @return array{data: Collection<int, mixed>}
     */
    public function toArray(Request $request) {
        return [
            'data' => $this->collection,
        ];
    }
}
