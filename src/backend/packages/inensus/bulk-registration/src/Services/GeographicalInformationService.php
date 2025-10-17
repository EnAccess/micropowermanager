<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\GeographicalInformation;
use App\Models\Meter\Meter;
use App\Models\MiniGrid;
use Inensus\BulkRegistration\Helpers\GeographicalLocationFinder;

class GeographicalInformationService {
    /**
     * @var array<string, mixed>
     */
    private array $geographicalInformationConfig;

    /**
     * @param array<string, mixed> $geographicalInformationData
     */
    public function createRelatedDataIfDoesNotExists(array $geographicalInformationData, object $ownerModel): void {
        if ($geographicalInformationData) {
            $geographicalInformation = GeographicalInformation::query()->make($geographicalInformationData);
            $geographicalInformation->owner()->associate($ownerModel);
            $geographicalInformation->save();
        }
    }

    /**
     * @param array<string, mixed> $csvData
     */
    public function resolveCsvDataFromComingRow(array $csvData, object $ownerModel): void {
        $this->geographicalInformationConfig = config('bulk-registration.csv_fields.geographical_information');
        $geographicalInformationData = ['points' => ''];
        if ($ownerModel instanceof MiniGrid) {
            $this->createMiniGridRelatedGeographicalInformation($ownerModel);
        } else {
            $geographicalInformationData = $this->createMeterRelatedGeographicalInformation(
                $geographicalInformationData,
                $csvData,
                $ownerModel
            );
            $this->createRelatedDataIfDoesNotExists($geographicalInformationData, $ownerModel);
        }
    }

    private function createMiniGridRelatedGeographicalInformation(MiniGrid $ownerModel): bool {
        $miniGridId = $ownerModel->id;
        $geographicalInformation = GeographicalInformation::query()->with(['owner'])
            ->whereHasMorph(
                'owner',
                [MiniGrid::class],
                function ($q) use ($miniGridId) {
                    $q->where('id', $miniGridId);
                }
            )->first();

        if ($geographicalInformation->points !== '') {
            return false;
        }
        if ($geographicalInformation->owner instanceof MiniGrid) {
            $geographicalLocationFinder = app()->make(GeographicalLocationFinder::class);
            $geographicalCoordinatesResult = $geographicalLocationFinder->getCoordinatesGivenAddress($geographicalInformation->owner->name);
            $geographicalInformation->points = $geographicalCoordinatesResult['lat'].','.$geographicalCoordinatesResult['lng'];
        }

        return $geographicalInformation->save();
    }

    /**
     * @param array<string, mixed> $geographicalInformationData
     * @param array<string, mixed> $csvData
     *
     * @return false|array<string, mixed>
     */
    private function createMeterRelatedGeographicalInformation(
        array $geographicalInformationData,
        array $csvData,
        object $ownerModel,
    ): false|array {
        $meterId = $ownerModel->id;
        $geographicalInformation = GeographicalInformation::query()->with(['owner'])
            ->whereHasMorph(
                'owner',
                [Meter::class],
                function ($q) use ($meterId) {
                    $q->where('id', $meterId);
                }
            )->first();
        if ($geographicalInformation) {
            return false;
        }

        $geographicalInformationData['points'] = $csvData[$this->geographicalInformationConfig['household_latitude']].','.$csvData[$this->geographicalInformationConfig['household_longitude']];

        return $geographicalInformationData;
    }
}
