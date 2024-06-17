<?php

namespace App\Http\Middleware;

use App\Models\MiniGrid;
use App\Models\Restriction;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DataControllerMiddleware
{
    /**
     * @var MiniGrid
     */
    private $miniGrid;
    /**
     * @var Restriction
     */
    private $restriction;

    public function __construct(MiniGrid $miniGrid, Restriction $restriction)
    {
        $this->miniGrid = $miniGrid;
        $this->restriction = $restriction;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        $restriction = $this->restriction->select('limit')->where(
            'target',
            'enable-data-stream'
        )->first();
        try {
            $this->miniGrid
                ->where('id', $request->get('mini_grid_id'))
                ->where('data_stream', 1)
                ->firstOrFail();
        } catch (ModelNotFoundException $ex) {
            $enabledCount = $this->miniGrid->where('data_stream', 1)->count();
            if ($enabledCount >= $restriction->limit) {
                return response()->json(['data' => ['message' => 'not-allowed']], 409);
            }
            $miniGrid = $this->miniGrid->find($request->get('mini_grid_id'));
            $miniGrid->data_stream = 1;
            $miniGrid->save();
        }

        return $next($request);
    }
}
