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
        // Add geo_json column
        Schema::connection('tenant')->table('clusters', function (Blueprint $table) {
            $table->json('geo_json')->nullable()->after('geo_data');
        });

        // Migrate data from geo_data.geojson to geo_json
        $clusters = DB::connection('tenant')
            ->table('clusters')
            ->whereNotNull('geo_data')
            ->get();

        foreach ($clusters as $cluster) {
            $geoData = json_decode($cluster->geo_data, true);
            if (isset($geoData['geojson'])) {
                DB::connection('tenant')
                    ->table('clusters')
                    ->where('id', $cluster->id)
                    ->update([
                        'geo_json' => json_encode($geoData['geojson']),
                    ]);
            }
        }

        // Make geo_json non-nullable now that data is migrated
        Schema::connection('tenant')->table('clusters', function (Blueprint $table) {
            $table->json('geo_json')->nullable(false)->change();
        });

        // Drop geo_data column
        Schema::connection('tenant')->table('clusters', function (Blueprint $table) {
            $table->dropColumn('geo_data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        // Add geo_data column back
        Schema::connection('tenant')->table('clusters', function (Blueprint $table) {
            $table->json('geo_data')->nullable()->after('manager_id');
        });

        // Migrate data back from geo_json to geo_data
        // Note: We can't fully reconstruct the original geo_data structure
        // because we're losing metadata like display_name, lat, lon, etc.
        $clusters = DB::connection('tenant')
            ->table('clusters')
            ->whereNotNull('geo_json')
            ->get();

        foreach ($clusters as $cluster) {
            $geoJson = json_decode($cluster->geo_json, true);

            // Calculate center point from coordinates
            $lat = 0;
            $lon = 0;
            if (isset($geoJson['coordinates'][0])) {
                $coords = $geoJson['coordinates'][0];
                $count = count($coords);
                foreach ($coords as $coord) {
                    $lat += $coord[1];
                    $lon += $coord[0];
                }
                $lat = $lat / $count;
                $lon = $lon / $count;
            }

            // Reconstruct geo_data with minimal structure
            $geoData = [
                'type' => 'manual',
                'geojson' => $geoJson,
                'display_name' => '', // Will be empty, should use cluster.name
                'selected' => false,
                'draw_type' => 'draw',
                'lat' => $lat,
                'lon' => $lon,
                'leaflet_id' => null,
            ];

            DB::connection('tenant')
                ->table('clusters')
                ->where('id', $cluster->id)
                ->update([
                    'geo_data' => json_encode($geoData),
                ]);
        }

        // Make geo_data non-nullable
        Schema::connection('tenant')->table('clusters', function (Blueprint $table) {
            $table->json('geo_data')->nullable(false)->change();
        });

        // Drop geo_json column
        Schema::connection('tenant')->table('clusters', function (Blueprint $table) {
            $table->dropColumn('geo_json');
        });
    }
};
