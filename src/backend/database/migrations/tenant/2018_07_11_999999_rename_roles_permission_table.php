<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WARNING!
 *
 * This migration was added retrospectively when the value of `config('permission.table_names.roles')`
 * was modified from `roles_permissions` to `roles`.
 *
 * This causes issues as consequent migrations are creating and modifying `Role` objects directly.
 * As a result on a fresh installation of MPM these migrations would try to create entries on `roles`
 * table which doesn't yet exist.
 *
 * By adding this migration retospectively, we solve it by producing the following situation:
 *
 * **On new installations:**
 * We rename the the `roles_permissions` table to `roles` directly.
 * All `Role` modifications are carried out on the renamed `roles` table.
 *
 * **On Existing installations:**
 * Previous migration runs would have created `Role` modifications on `roles_permissions` table.
 * This migration simply renames `roles_permissions` table to `roles`.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::connection('tenant')->rename('roles_permission', 'roles');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->rename('roles', 'roles_permission');
    }
};
