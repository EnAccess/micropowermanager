<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLE_NAME = 'sms_parsing_rules';

    public function up(): void {
        if (!Schema::connection('tenant')->hasTable(self::TABLE_NAME)) {
            Schema::connection('tenant')->create(self::TABLE_NAME, function (Blueprint $table) {
                $table->increments('id');
                $table->string('provider_name')->unique();
                $table->text('template');
                $table->text('pattern');
                $table->string('sender_pattern')->nullable();
                $table->boolean('enabled')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists(self::TABLE_NAME);
    }
};
