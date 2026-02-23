<?php

use App\Models\City;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::connection('tenant')->table('cities', function (Blueprint $table) {
            $table->dropColumn('cluster_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::connection('tenant')->table('cities', function (Blueprint $table) {
            $table->integer('cluster_id')->after('country_id');
        });

        // Repopulate cluster_id from city -> minigrid -> cluster
        City::with('miniGrid')->chunkById(100, function ($cities) {
            foreach ($cities as $city) {
                if ($city->miniGrid && $city->miniGrid->cluster_id) {
                    DB::connection('tenant')->table('cities')
                        ->where('id', $city->id)
                        ->update([
                            'cluster_id' => $city->miniGrid->cluster_id,
                        ]);
                }
            }
        });
    }
};
