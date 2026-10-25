<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::connection('tenant')->hasTable('whatsapp_templates')) {
            return;
        }

        Schema::connection('tenant')->create('whatsapp_templates', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('type');
            $table->string('language')->default('en');
            $table->text('body');
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('whatsapp_templates');
    }
};
