<?php

use App\Helpers\RolesPermissionsPopulator;
use App\Models\Auth\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void {
        // Populate roles and permissions for existing companies
        RolesPermissionsPopulator::populate();

        // Get roles
        $owner = Role::where('name', 'owner')->where('guard_name', 'api')->first();
        $admin = Role::where('name', 'admin')->where('guard_name', 'api')->first();

        // Get all users
        $users = User::on('tenant')->orderBy('id')->get();

        if (!$users->isEmpty()) {
            $firstUser = $users->first();

            foreach ($users as $user) {
                // Skip if user already has a role
                if ($user->roles()->exists()) {
                    continue;
                }

                if ($user->is($firstUser)) {
                    $user->assignRole($owner);
                } else {
                    $user->assignRole($admin);
                }
            }
        }
    }

    public function down(): void {
        User::on('tenant')->each(fn ($user) => $user->syncRoles([]));
    }
};
