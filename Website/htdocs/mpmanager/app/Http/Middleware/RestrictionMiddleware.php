<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceUsers;
use App\Models\MiniGrid;
use App\Models\Restriction;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

/**
 * Class RestrictionMiddleware
 *
 * @package App\Http\Middleware
 *
 * Checks if an incoming request is still in the allowed limits that are defined in the restrictions table
 */
class RestrictionMiddleware
{

    /**
     * @var Restriction
     */
    private $restriction;
    /**
     * @var MiniGrid
     */
    private $miniGrid;
    /**
     * @var MaintenanceUsers
     */
    private $maintenanceUsers;

    public function __construct(Restriction $restriction, MiniGrid $miniGrid, MaintenanceUsers $maintenanceUsers)
    {
        $this->restriction = $restriction;
        $this->miniGrid = $miniGrid;
        $this->maintenanceUsers = $maintenanceUsers;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @param  $type
     * @return mixed
     */
    public function handle($request, Closure $next, $target)
    {

        try {
            $restriction = $this->restriction->where('target', $target)->firstOrFail();
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
            $users = $this->maintenanceUsers->count();
            if ($users >= $limit) {
                return false;
            }
        } elseif ($target === 'enable-data-stream' && $request->input('data_stream') === 1) {
            // someone(admin) is trying to enable data-stream capability on the mini-grid dashboard
            $enabled = $this->miniGrid->where('data_stream', 1)->count();
            if ($enabled >= $limit) {
                return false;
            }
        }
        // everything is still in limits
        return true;
    }
}
