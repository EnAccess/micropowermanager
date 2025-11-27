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
        DB::connection('tenant')->table('asset_types')->insert([
            'name' => 'Goods',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        DB::connection('tenant')->table('asset_types')
            ->where('name', 'Goods')
            ->delete();
    }
};
