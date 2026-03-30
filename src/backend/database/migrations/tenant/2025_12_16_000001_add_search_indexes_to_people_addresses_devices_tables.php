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
            $table->index('name');
            $table->index('surname');
        });

        Schema::connection('tenant')->table('addresses', function (Blueprint $table) {
            $table->index('phone');
        });

        Schema::connection('tenant')->table('devices', function (Blueprint $table) {
            $table->index('device_serial');
            $table->index('person_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::connection('tenant')->table('people', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['surname']);
        });

        Schema::connection('tenant')->table('addresses', function (Blueprint $table) {
            $table->dropIndex(['phone']);
        });

        Schema::connection('tenant')->table('devices', function (Blueprint $table) {
            $table->dropIndex(['device_serial']);
            $table->dropIndex(['person_id']);
        });
    }
};
