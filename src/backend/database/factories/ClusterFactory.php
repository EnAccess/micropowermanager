<?php

namespace Database\Factories;

use App\Models\Cluster;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;

/** @extends Factory<Cluster> */
class ClusterFactory extends Factory {
    protected $model = Cluster::class;

    public function __construct(
    ) {
        parent::__construct(...func_get_args());
        $this->faker->addProvider(new \Faker\Provider\en_NG\Address($this->faker));
    }
    
    public function configure(): static {
        return $this->afterCreating(function (Cluster $cluster): void {
            if ($cluster->location()->exists()) {
                return;
            }
    
            $cluster->location()->create([
                'points' => '',
                'geo_json' => $this->buildDefaultGeoJson($cluster->name),
            ]);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        // @phpstan-ignore-next-line varTag.unresolvableType
        /** @var \Faker\Generator&\Faker\Provider\en_NG\Address */
        $faker = $this->faker;
        $clusterName = 'Cluster '.$faker->county();
        $geoJson = $this->buildDefaultGeoJson($clusterName);

        return [
            'name' => $clusterName,
            // Kept for backward compatibility until tenant data migration removes the column.
            ...($this->shouldPersistLegacyGeoJsonOnCluster() ? ['geo_json' => $geoJson] : []),
        ];
    }
    
    /**
     * @return array<string, mixed>
     */
    private function buildDefaultGeoJson(string $clusterName): array {
        return [
            'type' => 'Feature',
            'properties' => [
                'name' => $clusterName,
            ],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [34.09735878800838, -1.0021831137920607],
                    [34.08104951280351, -1.0037278294879668],
                    [34.08001945331692, -0.9961758791705448],
                    [34.079332746992485, -0.9831315606570004],
                    [34.08036280647911, -0.9745497443261169],
                    [34.09890387723834, -0.9889671831741885],
                    [34.09735878800838, -1.0021831137920607],
                ]],
            ],
        ];
    }

    private function shouldPersistLegacyGeoJsonOnCluster(): bool {
        return Schema::connection('tenant')->hasColumn('clusters', 'geo_json');
    }
}
