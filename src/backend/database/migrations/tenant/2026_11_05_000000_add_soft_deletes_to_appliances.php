<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('appliances', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void {
        if (Schema::connection('tenant')->hasColumn('appliances', 'deleted_at')) {
            Schema::connection('tenant')->table('appliances', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
