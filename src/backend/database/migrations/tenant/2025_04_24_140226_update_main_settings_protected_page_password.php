<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        DB::connection('tenant')->table('main_settings')
            ->where(function ($query) {
                $query->whereNull('protected_page_password')
                    ->orWhere('protected_page_password', 'null');
            })
            ->update(['protected_page_password' => '123123']);

        Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
            $table->longText('protected_page_password')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
            $table->longText('protected_page_password')->nullable()->change();
        });
    }
};
