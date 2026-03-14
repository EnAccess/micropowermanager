<?php

use App\Models\Village;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::connection('tenant')->table('villages', function (Blueprint $table) {
            $table->dropColumn('cluster_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::connection('tenant')->table('villages', function (Blueprint $table) {
            $table->integer('cluster_id')->after('country_id');
        });

        // Repopulate cluster_id from village -> minigrid -> cluster
        Village::with('miniGrid')->chunkById(100, function ($villages) {
            foreach ($villages as $village) {
                if ($village->miniGrid && $village->miniGrid->cluster_id) {
                    DB::connection('tenant')->table('villages')
                        ->where('id', $village->id)
                        ->update([
                            'cluster_id' => $village->miniGrid->cluster_id,
                        ]);
                }
            }
        });
    }
};
