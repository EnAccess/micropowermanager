<?php

namespace App\Http\Middleware;

use App\Models\Restriction;
use App\Services\MaintenanceUserService;
use App\Services\MiniGridService;
use App\Services\RestrictionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

/**
 * Class RestrictionMiddleware.
 */
class RestrictionMiddleware
{
    public function __construct(
        private RestrictionService $restrictionService,
        private MaintenanceUserService $maintenanceUserService,
        private MiniGridService $miniGridService,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Request  $request
     * @param \Closure $next
     * @param          $type
     *
     * @return mixed
     */
    public function handle($request, \Closure $next, $target)
    {
        try {
            $restriction = $this->restrictionService->getRestrictionForTarget($target);
            $restrictionResult = $this->handleRestriction($restriction->limit, $target, $request);

            if (!$restrictionResult) {
                $baseMessage = 'Your free limit of %s is exceeded. You can order more slots below.';

                if ($target === 'maintenance-user') {
                    $message = sprintf($baseMessage, 'External Maintenance Users');
                    $url = config('services.payment.maintenance');
                } else {
                    $message = sprintf($baseMessage, 'MiniGrid Data-logger');
                    $url = config('services.payment.maintenance');
                }

                return response()->json(['data' => ['message' => $message, 'url' => $url, 'status' => 409]], 409);
            }
        } catch (ModelNotFoundException $exception) { // there is no restriction found for that target.
            return $next($request);
        }

        return $next($request);
    }

    private function handleRestriction(int $limit, $target, Request $request): bool
    {
        if ($target === 'maintenance-user') {
            if ($this->maintenanceUserService->getMaintenanceUsersCount() >= $limit) {
                return false;
            }
        }

        // everything is still in limits
        return true;
    }
}
