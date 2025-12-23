<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Adds indexes to improve person search query performance.
     */
    public function up(): void {
        Schema::connection('tenant')->table('people', function (Blueprint $table) {
            $table->index('name', 'idx_people_name');
            $table->index('surname', 'idx_people_surname');
        });

        Schema::connection('tenant')->table('addresses', function (Blueprint $table) {
            $table->index(['owner_type', 'owner_id'], 'idx_addresses_owner');
            $table->index('phone', 'idx_addresses_phone');
        });

        Schema::connection('tenant')->table('devices', function (Blueprint $table) {
            $table->index('device_serial', 'idx_devices_device_serial');
            $table->index('person_id', 'idx_devices_person_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::connection('tenant')->table('people', function (Blueprint $table) {
            $table->dropIndex('idx_people_name');
            $table->dropIndex('idx_people_surname');
        });

        Schema::connection('tenant')->table('addresses', function (Blueprint $table) {
            $table->dropIndex('idx_addresses_owner');
            $table->dropIndex('idx_addresses_phone');
        });

        Schema::connection('tenant')->table('devices', function (Blueprint $table) {
            $table->dropIndex('idx_devices_device_serial');
            $table->dropIndex('idx_devices_person_id');
        });
    }
};
