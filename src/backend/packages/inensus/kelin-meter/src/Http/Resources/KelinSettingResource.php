<?php

namespace Inensus\KelinMeter\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KelinSettingResource extends JsonResource {
    public function toArray($request) {
        return [
            'data' => [
                'type' => 'setting',
                'id' => $this->id,
                'attributes' => [
                    'id' => $this->setting->id,
                    'actionName' => $this->setting->action_name,
                    'syncInValueStr' => $this->setting->sync_in_value_str,
                    'syncInValueNum' => $this->setting->sync_in_value_num,
                    'maxAttempts' => $this->setting->max_attempts,
                ],
            ],
        ];
    }
}
