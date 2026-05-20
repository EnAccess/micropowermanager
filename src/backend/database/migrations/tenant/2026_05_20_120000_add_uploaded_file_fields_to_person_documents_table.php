<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('person_documents', function (Blueprint $table) {
            $table->string('category')->default('identity_record')->after('person_id');
            $table->string('original_name')->nullable()->after('name');
            $table->string('mime_type')->nullable()->after('original_name');
            $table->unsignedInteger('file_size')->nullable()->after('mime_type');
            $table->json('additional_json')->nullable()->after('file_size');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('person_documents', function (Blueprint $table) {
            $table->dropColumn(['category', 'original_name', 'mime_type', 'file_size', 'additional_json']);
        });
    }
};
