<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Cluster;
use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
use App\Models\User;
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class ClusterSeeder extends Seeder {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionDemoCompany();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $clusterAdmin = User::factory()
            ->clusterAdmin()
            ->create();

        $clusters = Cluster::factory()
            ->count(2)
            ->for($clusterAdmin, 'manager')
            ->sequence(
                [
                    'name' => 'Cluster Mafia Island',
                    'geo_data' => json_decode('{"leaflet_id": 110,"type": "manual","geojson": {"type": "Polygon","coordinates": [[[-7.630225,39.961513],[-7.652002,39.631923],[-7.910525,39.549526],[-8.125383,39.631923],[-8.092754,39.934047],[-7.869716,39.988979],[-7.630225,39.961513]]]},"display_name": "Cluster Mafia Island","selected": false,"draw_type": "draw","lat": -7.317921,"lon": 39.985283}'),
                ],
                [
                    'name' => 'Cluster Pemba Island',
                    'geo_data' => json_decode('{"leaflet_id": 111,"type": "manual","geojson":{"type": "Polygon","coordinates":[[[-4.800463,39.770765],[-4.937297,39.520826],[-5.421455,39.545546],[-5.582757,39.647169],[-5.448798,39.905348],[-5.194467,39.95204],[-4.86341,39.899855],[-4.800463,39.770765]]]},"display_name": "Cluster Pemba Island","selected": false,"draw_type": "draw","lat": -4.902953,"lon": 39.774662}'),
                ],
            )
            ->create();

        // MiniGrids and Villages on Mafia Island
        $miniGridsMafiaIsland = MiniGrid::factory()
            ->count(2)
            ->for($clusters[0])
            ->sequence(
                ['name' => 'Mafia'],
                ['name' => 'Jibondo Island'],
            )
            ->has(
                GeographicalInformation::factory()->sequence(
                    ['points' => '-7.873645,39.754433'],
                    ['points' => '-8.0573,39.7253123'],
                ),
                'location'
            )
            ->has(
                City::factory()
                    ->for($clusters[0])
                    ->sequence(
                        ['name' => 'Mafia Village'],
                        ['name' => 'Jibondo Island Village'],
                    )
                    ->has(
                        GeographicalInformation::factory()->sequence(
                            ['points' => '-7.873645,39.754433'],
                            ['points' => '-8.0573,39.7253123'],
                        ),
                        'location'
                    )
            )
            ->create();

        // MiniGrids and Villages on Pemba Island
        $miniGridsPembaIsland = MiniGrid::factory()
            ->count(3)
            ->for($clusters[1])
            ->sequence(
                ['name' => 'Konde'],
                ['name' => 'Wette'],
                ['name' => 'Tumbe'],
            )
            ->has(
                GeographicalInformation::factory()->sequence(
                    ['points' => '-4.942415,39.745391'],
                    ['points' => '-5.056707,39.728021'],
                    ['points' => '-4.950556,39.786280'],
                ),
                'location'
            )
            ->has(
                City::factory()
                    ->for($clusters[1])
                    ->sequence(
                        ['name' => 'Konde Village'],
                        ['name' => 'Wette Village'],
                        ['name' => 'Tumbe Village'],
                    )
                    ->has(
                        GeographicalInformation::factory()->sequence(
                            ['points' => '-4.942415,39.745391'],
                            ['points' => '-5.056707, 39.728021'],
                            ['points' => '-4.950556,39.786280'],
                        ),
                        'location'
                    )
            )
            ->create();
    }
}
