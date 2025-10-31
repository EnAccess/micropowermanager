<?php

use App\Helpers\RolesPermissionsPopulator;
use App\Models\Agent;
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
        $fieldAgent = Role::where('name', 'field-agent')->where('guard_name', 'agent')->first();

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

        // Get all agents
        $agents = Agent::on('tenant')->orderBy('id')->get();

        foreach ($agents as $agent) {
            // Skip if agent already has a role
            if ($agent->roles()->exists()) {
                continue;
            }

            // Assign field-agent role to all agents
            $agent->assignRole($fieldAgent);
        }
    }

    public function down(): void {
        User::on('tenant')->each(fn ($user) => $user->syncRoles([]));
        Agent::on('tenant')->each(fn ($agent) => $agent->syncRoles([]));
    }
};
