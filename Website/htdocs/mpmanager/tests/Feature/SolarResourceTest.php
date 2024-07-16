<?php

namespace Tests\Feature;

use App\Models\Cluster;
use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
use App\Models\Solar;
use App\Models\WeatherData;
use Carbon\Carbon;
use Database\Factories\SolarFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SolarResourceTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function testCreateSolarEntry(): void
    {
        $headers = $this->headers();
        Cluster::create([
            'name' => 'Test Cluster',
            'manager_id' => 1,
            'geo_type' => 'manual',
            'geo_data' => [
                'leaflet_id' => 1096,
                'type' => 'manual',
                'geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [
                        [
                            [
                                40.205111559596,
                                29.08639311236141,
                            ],
                            [
                                40.23762584107582,
                                29.24514322081623,
                            ],
                            [
                                40.25286179446997,
                                29.06681420052294,
                            ],
                        ],
                    ],
                ],
                'searched' => false,
                'display_name' => 'asd',
                'selected' => true,
                'draw_type' => 'draw',
                'lat' => 40.231866398380596,
                'lon' => 29.132783511233526,
            ],
        ]);
        MiniGrid::create(['cluster_id' => 1, 'name' => 'Test-Grid', 'data_stream' => 0])->save();
        GeographicalInformation::UpdateOrCreate(['id' => 1],
            ['points' => '-1.8727924588344,33.022885322571', 'owner_type' => 'mini-grid', 'owner_id' => 1])->save();
        $requestData = [
            'node_id' => 1,
            'device_id' => 'faker-node',
            'mini_grid_id' => 1,
            'solar_reading' => [
                'starting_time' => '2020-06-17 18:22:30',
                'readings' => 300,
                'total' => 123,
                'average' => 412,
            ],
            'time_stamp' => Carbon::now()->timestamp,
        ];
        $request = $this->post('/api/solar', $requestData, ['accept' => 'application/json'], $headers);
        $request->assertStatus(201);
        $this->assertCount(1, WeatherData::all());
    }

    public function testListSolarsByMiniGrid()
    {
        $solarToCreate = 2;
        $headers = $this->headers();
        Cluster::create([
            'name' => 'Test Cluster',
            'manager_id' => 1,
            'geo_type' => 'manual',
            'geo_data' => [
                'leaflet_id' => 1096,
                'type' => 'manual',
                'geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [
                        [
                            [
                                40.205111559596,
                                29.08639311236141,
                            ],
                            [
                                40.23762584107582,
                                29.24514322081623,
                            ],
                            [
                                40.25286179446997,
                                29.06681420052294,
                            ],
                        ],
                    ],
                ],
                'searched' => false,
                'display_name' => 'asd',
                'selected' => true,
                'draw_type' => 'draw',
                'lat' => 40.231866398380596,
                'lon' => 29.132783511233526,
            ],
        ]);
        MiniGrid::create(['cluster_id' => 1, 'name' => 'Test-Grid', 'data_stream' => 0])->save();
        GeographicalInformation::UpdateOrCreate(['id' => 1],
            ['points' => '-1.8727924588344,33.022885322571', 'owner_type' => 'mini-grid', 'owner_id' => 1])->save();
        SolarFactory::times($solarToCreate)->create();
        $this->assertCount($solarToCreate, Solar::all());
        $request = $this->get('/api/mini-grids/1/solar-readings', $headers);
        $request->assertStatus(200);
        $this->assertCount($solarToCreate, $request->json('data'));
    }
}
