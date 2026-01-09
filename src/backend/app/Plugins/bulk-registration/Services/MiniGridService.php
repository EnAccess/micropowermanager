<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\MiniGrid;
use Inensus\BulkRegistration\Exceptions\MiniGridNotFoundException;

class MiniGridService extends CreatorService {
    public function __construct(MiniGrid $miniGrid) {
        parent::__construct($miniGrid);
    }

    /**
     * @param array<string, mixed> $csvData
     */
    public function resolveCsvDataFromComingRow(array $csvData) {
        $miniGridConfig = config('bulk-registration.csv_fields.mini_grid');

        if (!$csvData[$miniGridConfig['name']]) {
            throw new MiniGridNotFoundException('Mini Grid Name is required');
        }

        $registeredMiniGrid = MiniGrid::query()->where('name', $csvData[$miniGridConfig['name']])->first();

        if (!$registeredMiniGrid) {
            $message = 'There is no registered Mini Grid for '.$csvData[$miniGridConfig['name']].
                '. Please add the Mini Grid first.';
            throw new MiniGridNotFoundException($message);
        }

        $miniGridData = [
            'cluster_id' => $csvData[$miniGridConfig['cluster_id']],
            'name' => $csvData[$miniGridConfig['name']],
        ];

        return $this->createRelatedDataIfDoesNotExists($miniGridData);
    }
}
