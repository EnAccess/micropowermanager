<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::connection('tenant')->table('people')
            ->whereIn('id', DB::connection('tenant')->table('agents')->select('person_id'))
            ->update(['type' => 'agent']);
    }

    public function down(): void {}
};
