<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::connection('tenant')->hasTable('whatsapp_templates')) {
            Schema::connection('tenant')->create('whatsapp_templates', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('meta_template_id')->unique();
                $table->string('name')->index();
                $table->string('language');
                $table->string('category');
                $table->string('status');
                $table->string('parameter_format');
                $table->json('components');
                $table->string('rejected_reason')->nullable();
                $table->text('rejection_detail')->nullable();
                $table->text('rejection_recommendation')->nullable();
                $table->text('status_detail')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('whatsapp_templates');
    }
};
