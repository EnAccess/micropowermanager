<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController
{
    /**
     * @var Report
     */
    private $report;

    public function __construct(Report $report)
    {
        $this->report = $report;
    }

    public function download($id)
    {
        if (!$id) {
            return;
        }
        $report = $this->report->find($id);

        return response()->download(explode('*',$report->path)[0]);
    }

    public function index(Request $request): ApiResource
    {
        $type = $request->get('type');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        $reports = null;
        switch ($type) {
            case 'weekly':
                $reports = $this->getWeeklyReports($startDate, $endDate);
                break;
            case 'monthly':
                $reports = $this->getMonthlyReports($startDate, $endDate);
                break;
            default:
                $reports = $this->getAllReports($startDate, $endDate);
                break;
        }
        return new ApiResource($reports);
    }


    private function getWeeklyReports($startDate, $endDate)
    {
        return $this->report->where('type', 'weekly')->paginate(15);
    }

    private function getMonthlyReports($startDate, $endDate)
    {
        return $this->report->where('type', 'monthly')
            ->paginate(15);
    }

    private function getAllReports($startDate, $endDate)
    {
        return $this->report->paginate(15);
    }
}
