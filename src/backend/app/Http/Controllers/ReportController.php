<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController {
    public function __construct(private Report $report) {}

    public function download(int $id): StreamedResponse {
        $report = $this->report->find($id);
        $relativePath = explode('*', $report->path)[0];

        if (!Storage::exists($relativePath)) {
            abort(404, 'Report file not found.');
        }

        return Storage::download($relativePath);
    }

    public function index(Request $request): ApiResource {
        $type = $request->get('type');
        $request->get('startDate');
        $request->get('endDate');

        $reports = match ($type) {
            'weekly' => $this->getWeeklyReports(),
            'monthly' => $this->getMonthlyReports(),
            default => $this->getAllReports(),
        };

        return new ApiResource($reports);
    }

    /**
     * @return LengthAwarePaginator<int, Report>
     */
    private function getWeeklyReports(): LengthAwarePaginator {
        return $this->report->where('type', 'weekly')->paginate(15);
    }

    /**
     * @return LengthAwarePaginator<int, Report>
     */
    private function getMonthlyReports(): LengthAwarePaginator {
        return $this->report->where('type', 'monthly')
            ->paginate(15);
    }

    /**
     * @return LengthAwarePaginator<int, Report>
     */
    private function getAllReports(): LengthAwarePaginator {
        return $this->report->paginate(15);
    }
}
