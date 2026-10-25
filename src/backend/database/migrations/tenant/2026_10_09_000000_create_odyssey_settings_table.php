<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->create('odyssey_settings', function (Blueprint $table) {
            $table->id();
            $table->string('financing_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('odyssey_settings');
    }
};
