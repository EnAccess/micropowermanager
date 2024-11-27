<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('database_proxies', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->integer('fk_company_id');
            $table->integer('fk_company_database_id');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('database_proxies');
    }
};
