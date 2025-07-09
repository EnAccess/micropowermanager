<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        $map = [
            '/settings' => '/settings/configuration',
            '/profile/management' => '/settings/user-management',
            '/connection-groups' => '/settings/connection-groups',
            '/connection-types' => '/settings/connection-types',
        ];

        foreach ($map as $oldValue => $newValue) {
            DB::table('protected_pages')
                ->where('name', $oldValue)
                ->update([
                    'name' => $newValue,
                    'updated_at' => Carbon::now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        $reverse_map = [
            '/settings/configuration' => '/settings',
            '/settings/user-management' => '/profile/management',
            '/settings/connection-groups' => '/connection-groups',
            '/settings/connection-types' => '/connection-types',
        ];

        foreach ($reverse_map as $oldValue => $newValue) {
            DB::table('protected_pages')
                ->where('name', $oldValue)
                ->update([
                    'name' => $newValue,
                    'updated_at' => Carbon::now(),
                ]);
        }
    }
};
