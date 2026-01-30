<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix trigger_type column to be a string for proper polymorphic relations.
 * A previous migration changed it to integer, but Laravel's morphTo expects string.
 */
return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('sms', function (Blueprint $table) {
            $table->string('trigger_type')->nullable()->change();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('sms', function (Blueprint $table) {
            $table->integer('trigger_type')->nullable()->change();
        });
    }
};
