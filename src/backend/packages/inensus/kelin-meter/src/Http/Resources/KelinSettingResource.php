<?php

namespace Inensus\KelinMeter\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Inensus\KelinMeter\Models\KelinSetting as KelinSettingData;

/**
 * @mixin KelinSettingData
 */
class KelinSettingResource extends JsonResource {
    public function toArray($request) {
        return [
            'data' => [
                'type' => 'setting',
                'id' => $this->id,
                'attributes' => [
                    'id' => $this->setting->id, // @phpstan-ignore property.notFound
                    'actionName' => $this->setting->action_name, // @phpstan-ignore property.notFound
                    'syncInValueStr' => $this->setting->sync_in_value_str, // @phpstan-ignore property.notFound
                    'syncInValueNum' => $this->setting->sync_in_value_num, // @phpstan-ignore property.notFound
                    'maxAttempts' => $this->setting->max_attempts, // @phpstan-ignore property.notFound
                ],
            ],
        ];
    }
}
