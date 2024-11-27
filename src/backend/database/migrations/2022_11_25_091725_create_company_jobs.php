<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('company_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('job_name');
            $table->string('job_uuid')->nullable();
            $table->integer('status')->default(0);
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('company_jobs');
        Schema::enableForeignKeyConstraints();
    }
};
