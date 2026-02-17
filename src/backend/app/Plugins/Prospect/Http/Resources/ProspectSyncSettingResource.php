<?php

namespace App\Plugins\Prospect\Http\Resources;

use App\Plugins\Prospect\Models\ProspectSyncSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProspectSyncSetting
 */
class ProspectSyncSettingResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        return [
            'data' => [
                'type' => 'sync_setting',
                'id' => $this->id,
                'attributes' => [
                    'id' => $this->id,
                    'actionName' => $this->action_name,
                    'isEnabled' => $this->is_enabled ?? true,
                    'syncInValueStr' => $this->sync_in_value_str,
                    'syncInValueNum' => $this->sync_in_value_num,
                    'maxAttempts' => $this->max_attempts,
                ],
            ],
        ];
    }
}
