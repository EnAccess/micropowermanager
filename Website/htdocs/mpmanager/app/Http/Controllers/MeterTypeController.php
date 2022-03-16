<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeterTypeCreateRequest;
use App\Http\Requests\MeterTypeUpdateRequest;
use App\Http\Resources\ApiResource;
use App\Models\Meter\MeterType;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

/**
 * @group   MeterTypes
 * Class MeterTypeController
 * @package App\Http\Controllers
 */
class MeterTypeController extends Controller
{
    use SoftDeletes;

    /**
     * List
     *
     * @responseFile responses/metertypes/meter.types.list.json
     *
     * @return ApiResource
     */
    public function index(): ApiResource
    {
        return new ApiResource(
            MeterType::paginate(15)
        );
    }


    /**
     * Store
     * Creates a new meter type
     *
     * @bodyParam online int required
     * @bodyParam phase int required
     * @bodyParam max_current int required
     *
     * @param  MeterTypeCreateRequest $request
     * @return ApiResource
     */
    public function store(MeterTypeCreateRequest $request)
    {
        return
            new ApiResource(
                MeterType::create(
                    request()->only(
                        [
                        'online',
                        'phase',
                        'max_current',
                        ]
                    )
                )
            );
    }

    /**
     * Detail
     *
     * @bodyParam id int required
     *
     * @param  int $id
     * @return ApiResource
     */
    public function show($id)
    {
        return new ApiResource(
            MeterType::findOrFail($id)
        );
    }


    /**
     * Update
     * Updates the given meter type
     *
     * @urlParam  id required
     * @bodyParam online int required
     * @bodyParam phase int required
     * @bodyParam max_current int required
     *
     * @param  MeterTypeUpdateRequest $request
     * @param  MeterType              $meterType
     * @return ApiResource
     */
    public function update(MeterTypeUpdateRequest $request, MeterType $meterType)
    {
        $meterType->update($request->only(['online','phase','max_current']));
        $meterType->fresh();
        return new ApiResource($meterType);
    }

    /**
     * List with Meters
     * Displays the meter types with the associated meters
     *
     * @urlParam id required
     *
     * @responseFile responses/metertypes/metertypes.meter.list.json
     * @param        Request $request
     * @param        $id
     * @return       ApiResource
     */
    public function meterList(Request $request, $id)
    {
        return new ApiResource(
            MeterType::with('meters')->findOrFail($id)
        );
    }
}
