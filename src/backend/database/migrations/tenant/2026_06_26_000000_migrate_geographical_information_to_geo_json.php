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
        Schema::connection('tenant')->table('geographical_informations', function (Blueprint $table) {
            $table->json('geo_json')->nullable()->after('points');
        });

        DB::connection('tenant')
            ->table('geographical_informations')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    [$longitude, $latitude] = $this->parsePoints($row->points);

                    DB::connection('tenant')
                        ->table('geographical_informations')
                        ->where('id', $row->id)
                        ->update([
                            'geo_json' => json_encode([
                                'type' => 'Feature',
                                'geometry' => [
                                    'type' => 'Point',
                                    'coordinates' => [$longitude, $latitude],
                                ],
                                'properties' => new stdClass(),
                            ]),
                        ]);
                }
            });

        Schema::connection('tenant')->table('geographical_informations', function (Blueprint $table) {
            $table->dropColumn('points');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('geographical_informations', function (Blueprint $table) {
            $table->text('points')->nullable()->after('owner_type');
        });

        DB::connection('tenant')
            ->table('geographical_informations')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $geoJson = json_decode((string) $row->geo_json, true);
                    $coordinates = (is_array($geoJson) ? ($geoJson['geometry']['coordinates'] ?? null) : null) ?? [0, 0];
                    $longitude = $coordinates[0] ?? 0;
                    $latitude = $coordinates[1] ?? 0;

                    DB::connection('tenant')
                        ->table('geographical_informations')
                        ->where('id', $row->id)
                        ->update([
                            'points' => $latitude.','.$longitude,
                        ]);
                }
            });

        Schema::connection('tenant')->table('geographical_informations', function (Blueprint $table) {
            $table->text('points')->nullable(false)->change();
        });

        Schema::connection('tenant')->table('geographical_informations', function (Blueprint $table) {
            $table->dropColumn('geo_json');
        });
    }

    /**
     * Parse a stored "lat,lng" string into GeoJSON [longitude, latitude] order.
     *
     * @return array{0: float, 1: float}
     */
    private function parsePoints(?string $points): array {
        [$latitude, $longitude] = array_pad(explode(',', (string) $points, 2), 2, null);

        return [(float) $longitude, (float) $latitude];
    }
};
