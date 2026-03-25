<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('micro_power_manager')->create('pending_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue', 255)->index();
            $table->string('connection', 255)->default('redis');
            $table->longText('payload');
            $table->unsignedInteger('delay_seconds')->default(0);
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void {
        Schema::connection('micro_power_manager')->dropIfExists('pending_jobs');
    }
};
