<?php

namespace Inensus\Ticket\Services;

use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Inensus\Ticket\Models\TicketOutsourcePayoutReport;

/**
 * @implements IBaseService<TicketOutsourcePayoutReport>
 */
class TicketOutsourcePayoutReportService implements IBaseService {
    public function __construct(
        private TicketOutsourcePayoutReport $TicketOutsourcePayoutReport,
    ) {}

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->TicketOutsourcePayoutReport->newQuery()->paginate($limit);
        }

        return $this->TicketOutsourcePayoutReport->newQuery()->get();
    }

    public function create(array $TicketOutsourcePayoutReportData): TicketOutsourcePayoutReport {
        return $this->TicketOutsourcePayoutReport->newQuery()->create($TicketOutsourcePayoutReportData);
    }

    public function getById(int $outsourceReportId): TicketOutsourcePayoutReport {
        return $this->TicketOutsourcePayoutReport->newQuery()->find($outsourceReportId);
    }

    public function update($model, array $data): TicketOutsourcePayoutReport {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
