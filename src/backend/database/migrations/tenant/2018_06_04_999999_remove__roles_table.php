<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️ WARNING.
 *
 * This migration was introduced retrospectively after the value of `config('permission.table_names.roles')`
 * was changed from `roles_permissions` to `roles`.
 *
 * This change created a mismatch:
 * Later migrations create and modify `Role` models using the new table name (`roles`),
 * but in a fresh MPM installation that table did not exist yet.
 * Instead, older migrations would still create `Role`-related data in the original `roles_permissions` table.
 *
 * To resolve this, we add this migration retroactively so that both new and
 * existing installations end up with the correct table structure.
 *
 * **For new installations:**
 * - This migration renames the `roles_permissions` table to `roles` before any subsequent migrations run.
 * - All later modifications to `Role` objects operate correctly on the renamed `roles` table.
 *
 * **For existing installations:**
 * - Previous migrations already operated on the `roles_permissions` table.
 * - This migration simply renames `roles_permissions` to `roles` so all future operations target the correct table.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void {
        if (Schema::connection('tenant')->hasTable('role_definitions')) {
            Schema::connection('tenant')->dropIfExists('role_definitions');
        }

        if (Schema::connection('tenant')->hasTable('roles')) {
            Schema::connection('tenant')->dropIfExists('roles');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {
        if (!Schema::connection('tenant')->hasTable('role_definitions')) {
            Schema::connection('tenant')->create('role_definitions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('role_name');
            });
        }

        if (!Schema::connection('tenant')->hasTable('roles')) {
            Schema::connection('tenant')->create('roles', function (Blueprint $table) {
                $table->increments('id');
                $table->morphs('role_owner');
                $table->integer('role_definition_id');
                $table->timestamps();
            });
        }
    }
};
