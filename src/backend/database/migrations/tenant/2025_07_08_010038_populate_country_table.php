<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        $country = Storage::disk('local')->get('countries.json');
        $countries = json_decode($country, true);

        if (is_array($countries)) {
            foreach ($countries as $code => $name) {
                DB::connection('tenant')->table('countries')->insert([
                    'country_code' => $code,
                    'country_name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        DB::connection('tenant')->table('countries')->truncate();
    }
};
