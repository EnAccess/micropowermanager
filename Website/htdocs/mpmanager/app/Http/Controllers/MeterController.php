<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Http\Services\MeterService;
use App\Models\City;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterConsumption;
use App\Models\Meter\MeterToken;
use App\Models\PaymentHistory;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class MeterController
 *
 * @package App\Http\Controllers
 * @group   Meters
 */
class MeterController extends Controller
{

    /**
     * @var Meter
     */
    private $meter;
    /**
     * @var City
     */
    private $city;

    private $meterService;

    /**
     * MeterController constructor.
     *
     * @param Meter        $meter
     * @param City         $city
     * @param MeterService $meterService
     */
    public function __construct(
        Meter $meter,
        City $city,
        MeterService $meterService
    ) {
        $this->meter = $meter;
        $this->city = $city;
        $this->meterService = $meterService;
    }

    /**
     * List
     * Lists all used meters with meterType and meterParameters.tariff
     * The response is paginated with 15 results on each page/request
     *
     * @urlParam     page int
     * @urlParam     in_use int to list wether used or all meters
     * @responseFile responses/meters/meters.list.json
     * @return       ApiResource
     */
    public function index(): ApiResource
    {
        $inUse = request('in_use');

        if ($inUse !== null) {
            return new ApiResource(
                $this->meter::with('meterType', 'meterParameter.tariff')->where('in_use', $inUse)->paginate(15)
            );
        }
        return new ApiResource(
            $this->meter::with('meterType', 'meterParameter.tariff')->paginate(15)
        );
    }

    /**
     * Create
     * Stores a new meter
     *
     * @param     Request $request
     * @bodyParam serial_number string required
     * @bodyParam meter_type_id int required
     * @bodyParam manufacturer_id int required
     * @return    mixed
     * @throws    ValidationException
     */
    public function store(Request $request)
    {
        //validate
        $validation = Validator::make($request->all(), Meter::$rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        return
            new ApiResource(
                $this->meter::create(
                    request()->only(
                        [
                        'serial_number',
                        'meter_type_id',
                        'in_use',
                        'manufacturer_id',
                        ]
                    )
                )
            );
    }

    /**
     * Detail
     * Detailed meter with following relations
     * - MeterParameter.tariff
     * - MeterParameter.owner
     * - Meter Type
     * - MeterParameter.connectionType
     * - MeterParameter.connectionGroup
     * - Manufacturer
     *
     * @urlParam serialNumber string
     * @param    string $serialNumber
     *
     * @return ApiResource
     *
     * @responseFile responses/meters/meter.detail.json
     */
    public function show($serialNumber): ApiResource
    {
        return new ApiResource(
            Meter::with(
                'meterParameter.tariff',
                'meterParameter.owner',
                'meterType',
                'meterParameter.connectionType',
                'meterParameter.connectionGroup',
                'manufacturer'
            )
                ->where('serial_number', $serialNumber)
                ->first()
        );
    }

    /**
     * Search
     * The search term will be searched in following fields
     * - Tariff.name
     * - Serial number
     *
     * @bodyParam term string required
     *
     * @return ApiResource
     *
     * @responseFile responses/meters/meters.search.json
     */
    public function search()
    {
        $term = request('term');

        $meters = $this->meter::with('meterType', 'meterParameter.tariff')
            ->whereHas(
                'meterParameter.tariff',
                function ($q) use ($term) {
                    return $q->where('name', 'LIKE', '%' . $term . '%');
                }
            )
            ->orWhere(
                'serial_number',
                'LIKE',
                '%' . $term . '%'
            )->paginate(15);
        return new ApiResource($meters);
    }


    /**
     * Update
     * Updates the geo coordinates of the meter
     *
     * @urlParam  meter int
     * @bodyParam points string. Comma seperated latitude and longitude. Example 1,2
     *
     * @param Request $request
     * @param Meter   $meter
     */
    public function update(Request $request): ApiResource
    {
         $meters = $request->all();

         return new ApiResource($this->meterService->updateMeterGeoLocations($meters));
    }

    /**
     * @group    People
     * Person with Meters & Tariff
     * Person details with his/her owned meter(s) and its assigned tariff
     * @param    Person $person
     * @urlParam person required The ID of the person
     * @return   ApiResource
     *
     * @responseFile responses/people/person.meter.tariff.json
     */
    public function personMeters(Person $person): ApiResource
    {
        $meters = Person::with('meters.tariff', 'meters.meter')->find($person->id);
        return new ApiResource($meters);
    }

    /**
     * @group    People
     * Person with Meters & geo
     * Person details with his/her owned meter(s) and the geo coordinates where each meter is placed
     * - Meters
     *   - Meter coordinates
     * A list of meters which belong to that given person
     * The list is wether sorted or paginated
     * @urlParam person required The ID of the person
     * @param    Person $person
     * @return   ApiResource
     *
     * @responseFile responses/people/person.meter.list.json
     */
    public function meterGeo(Person $person): ApiResource
    {
        $meters = Person::with('meters.meter', 'meters.geo')->find($person->id);
        return new ApiResource($meters);
    }

    /**
     * List with all relation
     * The output is neither sorted nor paginated
     * The list contains following relations
     *
     * @urlParam     id The ID of the meter
     * @responseFile responses/meters/meter.with.all.relations.json
     * @param        $id
     * @return       ApiResource
     */
    public function allRelations($id)
    {
        $meterDetails = Meter::with(
            'meterParameter.tariff',
            'meterParameter.geo',
            'meterType'
        )->find($id);
        return new ApiResource($meterDetails);
    }

    /**
     * Meter Transactions
     * A list of transactions which belong for that meter
     * The list contains the latest transactions and is paginated. Each page contains 5 rows
     *
     * @urlParam serialNumber the serial number of the meter
     *
     * @responseFile responses/meters/meter.transaction.list.json
     *
     * @return ApiResource
     */
    public function transactionList($serialNumber): ApiResource
    {
        $token = new MeterToken();
        $transactions = new Transaction();
        $paymentHistory = new PaymentHistory();


        return new ApiResource(
            $paymentHistory::with('transaction', 'paidFor')
                ->whereHas(
                    'transaction',
                    function ($q) use ($serialNumber) {
                        $q->where('message', $serialNumber);
                    }
                )->latest()->paginate(5)
        );
    }

    /**
     * Consumption List
     * If the meter has the ability to send data to your server. That is the endpoint where you get the
     * meter readings ( used energy, credit on meter etc.)
     *
     * @urlParam     serialNumber
     * @urlParam     start YYYY-mm-dd format
     * @urlParam     end YYYY-mm-dd format
     * @responseFile responses/meters/meter.consumption.list.json
     *
     * @param  $serialNumber
     * @param  $start
     * @param  $end
     * @return ApiResource
     */
    public function consumptionList($serialNumber, $start, $end)
    {
        $meter = $this->meter->where('serial_number', $serialNumber)->first();
        $mc = new MeterConsumption();
        return new ApiResource(
            $mc->where('meter_id', $meter->id)->whereBetween(
                'reading_date',
                [$start, $end]
            )->orderBy('reading_date')->get()
        );
    }

    /**
     * List with geo and access rate
     * A list of meters with their positions and access rate payments
     * The list is not paginated.
     *
     * @urlParam mini_grid_id
     *
     * @return       ApiResource
     * @responseFile responses/meters/meters.geo.list.json
     */
    public function meterGeoList($miniGridId): ApiResource
    {


        $cities = $this->city->select('id')->where('mini_grid_id', $miniGridId)->get()->pluck('id')->toArray();

        //city id yi anca addresten yakalariz
        if ($miniGridId === null) {
            $meters = $this->meter::with('meterParameter.address.geo', 'accessRatePayment')->where(
                'in_use',
                1
            )->get();
        } else {
            $meters = $this->meter::with('meterParameter.address.geo', 'accessRatePayment')
                ->whereHas(
                    'meterParameter',
                    function ($q) use ($cities) {
                        $q->whereHas(
                            'address',
                            function ($q) use ($cities) {
                                $q->whereIn('city_id', $cities);
                            }
                        );
                    }
                )
                ->where('in_use', 1)->get();
        }

        return new ApiResource($meters);
    }

    /**
     * Delete
     * Deletes the meter with its all releations
     *
     * @urlParam meterId. The ID of the meter to be delete
     * @param    $meterId
     * @return   ApiResource
     */
    public function destroy($meterId)
    {
        $meter = $this->meter->find($meterId);
        if ($meter !== null) {
            $meter->delete();
        }
        return new ApiResource($meter);
    }
}
