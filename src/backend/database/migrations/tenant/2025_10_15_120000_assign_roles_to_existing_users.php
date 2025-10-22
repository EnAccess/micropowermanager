<?php

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void {
        // Ensure roles exist
        $owner = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'api']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $editor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'api']);
        $reader = Role::firstOrCreate(['name' => 'reader', 'guard_name' => 'api']);
        $fieldAgent = Role::firstOrCreate(['name' => 'field-agent', 'guard_name' => 'agent']);

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
