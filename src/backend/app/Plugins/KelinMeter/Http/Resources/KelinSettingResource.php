<?php

namespace App\Plugins\KelinMeter\Http\Resources;

use App\Plugins\KelinMeter\Models\KelinSetting;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin KelinSetting
 */
class KelinSettingResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param mixed $request
     *
     * @return array{
     *     data: array{
     *         type: 'setting',
     *         id: int,
     *         attributes: array{
     *             id: int,
     *             actionName: string,
     *             syncInValueStr: string,
     *             syncInValueNum: int,
     *             maxAttempts: int
     *         }
     *     }
     * }
     */
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
