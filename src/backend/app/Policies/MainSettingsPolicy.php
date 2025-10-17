<?php

namespace App\Policies;

use App\Models\MainSettings;
use App\Models\User;

class MainSettingsPolicy {
    public function view(User $user): bool {
        return $user->can('settings.view');
    }

    public function update(User $user, MainSettings $settings): bool {
        return $user->can('settings.update');
    }
}
