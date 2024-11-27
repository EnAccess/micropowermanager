<?php

namespace App\Listeners;

use App\Events\ClusterEvent;
use App\Exceptions\GeoFormatException;
use App\Models\Cluster;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery\Exception;

class ClusterGeoListener {
    /**
     * Handle the event.
     *
     * @param ClusterEvent $event
     *
     * @return void
     */
    public function handle(ClusterEvent $event): void {
        $cluster = $event->cluster;
        $type = $event->type;
        if ($type === 'external') {
            try {
                $data = $this->reformatExternalData($event->data);
            } catch (GeoFormatException $exception) {
                Log::critical(
                    'Cluster polygon creating failed. External geo-coordinate object is not well formatted.',
                    ['id' => '58gjw3f9w392de12dkbkwelkkud', 'message' => $exception->getMessage()]
                );

                return;
            }
        } else {
            $data = $event->data;
        }
        try {
            $this->storeData($cluster, $data);
        } catch (Exception $exception) {
            Log::critical(
                'Cluster Geo Coorinates could not be stored',
                ['id' => 'gjdfk8fkj49fkw3opfdgil', 'message' => $exception->getMessage()]
            );
        }
    }

    /**
     * Creates a json file which has the same name as the cluster.
     *
     * @param Cluster $cluster
     * @param         $data
     */
    private function storeData(Cluster $cluster, $data): void {
        Storage::disk('local')->put($cluster->name.'.json', json_encode($data));
    }

    /**
     * External geojson resource has lon,lat format. Change order to lat,lon.
     *
     * @param $data
     *
     * @return array[]
     *
     * @throws GeoFormatException
     *
     * @psalm-return list<array{0: mixed, 1: mixed}>
     */
    public function reformatExternalData($data): array {
        $formatted = [];
        if (is_array($data) && !array_key_exists('geojson', $data)) {
            $data = $data[0];
        }

        if (!array_key_exists('geojson', $data)) {
            throw new GeoFormatException('external resource should contain a geojson key');
        }

        if (!array_key_exists('coordinates', $data['geojson'])) {
            throw new GeoFormatException('geojson key has not coordinates key');
        }

        $coordinates = $data['geojson']['coordinates'][0];

        foreach ($coordinates as $coordinate) {
            $formatted[] = [$coordinate[1], $coordinate[0]];
        }

        return $formatted;
    }
}
