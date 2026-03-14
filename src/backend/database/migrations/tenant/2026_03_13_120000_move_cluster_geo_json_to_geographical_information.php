<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        $schema = Schema::connection('tenant');

        if (! $schema->hasColumn('geographical_informations', 'geo_json')) {
            $schema->table('geographical_informations', function (Blueprint $table): void {
                $table->json('geo_json')->nullable()->after('points');
            });
        }

        if (! $schema->hasColumn('clusters', 'geo_json')) {
            return;
        }

        $clusters = DB::connection('tenant')
            ->table('clusters')
            ->select(['id', 'geo_json'])
            ->whereNotNull('geo_json')
            ->get();

        foreach ($clusters as $cluster) {
            $existingGeo = DB::connection('tenant')
                ->table('geographical_informations')
                ->where('owner_type', 'cluster')
                ->where('owner_id', $cluster->id)
                ->first();

            if ($existingGeo) {
                DB::connection('tenant')
                    ->table('geographical_informations')
                    ->where('id', $existingGeo->id)
                    ->update([
                        'geo_json' => $cluster->geo_json,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::connection('tenant')
                ->table('geographical_informations')
                ->insert([
                    'owner_id' => $cluster->id,
                    'owner_type' => 'cluster',
                    'points' => '',
                    'geo_json' => $cluster->geo_json,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $schema->table('clusters', function (Blueprint $table): void {
            $table->dropColumn('geo_json');
        });
    }

    public function down(): void {
        $schema = Schema::connection('tenant');

        if (! $schema->hasColumn('clusters', 'geo_json')) {
            $schema->table('clusters', function (Blueprint $table): void {
                $table->json('geo_json')->nullable()->after('manager_id');
            });
        }

        if (! $schema->hasColumn('geographical_informations', 'geo_json')) {
            return;
        }

        $clusterGeo = DB::connection('tenant')
            ->table('geographical_informations')
            ->select(['owner_id', 'geo_json'])
            ->where('owner_type', 'cluster')
            ->whereNotNull('geo_json')
            ->get();

        foreach ($clusterGeo as $geoInfo) {
            DB::connection('tenant')
                ->table('clusters')
                ->where('id', $geoInfo->owner_id)
                ->update([
                    'geo_json' => $geoInfo->geo_json,
                    'updated_at' => now(),
                ]);
        }

        $schema->table('geographical_informations', function (Blueprint $table): void {
            $table->dropColumn('geo_json');
        });
    }
};
