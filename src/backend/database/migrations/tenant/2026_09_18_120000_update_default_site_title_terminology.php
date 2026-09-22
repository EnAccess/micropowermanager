<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        DB::connection('tenant')->table('main_settings')
            ->where('site_title', 'MPM - The easiest way to manage your Mini-Grid')
            ->update([
                'site_title' => 'MPM - The easiest way to manage your Site',
                'updated_at' => Carbon::now(),
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        DB::connection('tenant')->table('main_settings')
            ->where('site_title', 'MPM - The easiest way to manage your Site')
            ->update([
                'site_title' => 'MPM - The easiest way to manage your Mini-Grid',
                'updated_at' => Carbon::now(),
            ]);
    }
};
