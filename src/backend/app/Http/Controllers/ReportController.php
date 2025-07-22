<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController {
    /**
     * @var Report
     */
    private Report $report;

    public function __construct(Report $report) {
        $this->report = $report;
    }

    // $id could be int or string (depending on route config), adjust as needed
    public function download(int|string $id): ?BinaryFileResponse {
        if (!$id) {
            return null;
        }
        $report = $this->report->find($id);

        // If report not found, return null or some fallback response?
        if (!$report) {
            return null;
        }

        return response()->download(explode('*', $report->path)[0]);
    }

    public function index(Request $request): ApiResource {
        $type = $request->get('type');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        $reports = match ($type) {
            'weekly' => $this->getWeeklyReports($startDate, $endDate),
            'monthly' => $this->getMonthlyReports($startDate, $endDate),
            default => $this->getAllReports($startDate, $endDate),
        };

        return new ApiResource($reports);
    }

    /**
     * @return LengthAwarePaginator<Report>
     */
    private function getWeeklyReports(?string $startDate, ?string $endDate): LengthAwarePaginator {
        return $this->report->where('type', 'weekly')->paginate(15);
    }

    /**
     * @return LengthAwarePaginator<Report>
     */
    private function getMonthlyReports(?string $startDate, ?string $endDate): LengthAwarePaginator {
        return $this->report->where('type', 'monthly')->paginate(15);
    }

    /**
     * @return LengthAwarePaginator<Report>
     */
    private function getAllReports(?string $startDate, ?string $endDate): LengthAwarePaginator {
        return $this->report->paginate(15);
    }
}
