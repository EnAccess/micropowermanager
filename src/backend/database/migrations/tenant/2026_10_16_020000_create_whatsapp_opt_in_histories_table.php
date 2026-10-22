<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::connection('tenant')->hasTable('whatsapp_business_opt_in_histories')) {
            Schema::connection('tenant')->create('whatsapp_business_opt_in_histories', static function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('person_id')->index();
                $table->boolean('opted_in');
                $table->string('actor_type')->nullable(); // 'user', 'customer', 'system'
                $table->unsignedInteger('actor_id')->nullable();
                $table->string('source')->default('admin_ui'); // 'admin_ui', 'bulk_action', 'customer_registration', 'webhook_command', 'system'
                $table->string('note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('whatsapp_business_opt_in_histories');
    }
};
