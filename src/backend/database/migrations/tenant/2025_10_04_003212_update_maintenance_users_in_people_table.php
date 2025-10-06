<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // Migrate data from maintenance_users to people table
        // Set type to 'maintenance' and populate mini_grid_id for maintenance users
        DB::connection('tenant')->statement('
            UPDATE people 
            SET 
                type = "maintenance",
                mini_grid_id = (
                    SELECT mini_grid_id 
                    FROM maintenance_users 
                    WHERE maintenance_users.person_id = people.id
                )
            WHERE EXISTS (
                SELECT 1 
                FROM maintenance_users 
                WHERE maintenance_users.person_id = people.id
            )
        ');

        // Set type to 'agent' for people who are agents but not maintenance users
        DB::connection('tenant')->statement('
            UPDATE people 
            SET type = "agent"
            WHERE EXISTS (
                SELECT 1 
                FROM agents 
                WHERE agents.person_id = people.id
            )
            AND NOT EXISTS (
                SELECT 1 
                FROM maintenance_users 
                WHERE maintenance_users.person_id = people.id
            )
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        // Revert the migrated data
        // Set type back to 'customer' and clear mini_grid_id for maintenance users
        DB::connection('tenant')->statement('
            UPDATE people 
            SET 
                type = "customer",
                mini_grid_id = NULL
            WHERE EXISTS (
                SELECT 1 
                FROM maintenance_users 
                WHERE maintenance_users.person_id = people.id
            )
        ');

        // Revert agent types back to 'customer'
        DB::connection('tenant')->statement('
            UPDATE people 
            SET type = "customer"
            WHERE EXISTS (
                SELECT 1 
                FROM agents 
                WHERE agents.person_id = people.id
            )
            AND NOT EXISTS (
                SELECT 1 
                FROM maintenance_users 
                WHERE maintenance_users.person_id = people.id
            )
        ');
    }
};
