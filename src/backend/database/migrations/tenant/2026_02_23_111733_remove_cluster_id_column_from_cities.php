<?php

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

        DB::connection('tenant')
            ->table('cities')
            ->join('mini_grids', 'cities.mini_grid_id', '=', 'mini_grids.id')
            ->whereNotNull('mini_grids.cluster_id')
            ->update(['cities.cluster_id' => DB::raw('mini_grids.cluster_id')]);
    }
};
