<?php

use App\Helpers\RolesPermissionsPopulator;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void {
        RolesPermissionsPopulator::populate();
    }

    public function down(): void {
        // Do nothing - roles and permissions should not be rolled back
    }
};
