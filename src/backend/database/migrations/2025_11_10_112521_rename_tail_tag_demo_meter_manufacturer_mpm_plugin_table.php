<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        DB::table('mpm_plugins')->where('id', 21)->update([
            'tail_tag' => null,
        ]);
        DB::table('mpm_plugins')->where('id', 22)->update([
            'tail_tag' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        DB::table('mpm_plugins')->where('id', 21)->update([
            'tail_tag' => 'DemoMeterManufacturer',
        ]);
        DB::table('mpm_plugins')->where('id', 22)->update([
            'tail_tag' => 'DemoShsManufacturer',
        ]);
    }
};
