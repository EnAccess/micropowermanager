<?php

namespace App\ManufacturerApi;

use App\Exceptions\Manufacturer\MeterIsNotReadable;
use App\Lib\IMeterReader;
use App\Models\Meter\MeterConsumption;
use Carbon\Exceptions\InvalidDateException;
use Illuminate\Support\Facades\Log;

class CalinReadMeter implements IMeterReader
{
    public const READ_DAILY = 1;
    /**
     * @var \SoapClient
     */
    private $api;
    /**
     * @var MeterConsumption
     */
    private $consumption;

    /**
     * CalinReadMeter constructor.
     *
     * @param MeterConsumption $consumption
     *
     * @throws \SoapFault
     */
    public function __construct(MeterConsumption $consumption)
    {
        try {
            $this->api = new \SoapClient(\config('services.calin.meter.api'), ['keep_alive' => false]);
        } catch (\Exception $exception) {
        }
        $this->consumption = $consumption;
    }

    /**
     * @param string $meterId serial number of the meter
     * @param string $date    y-m-d format date
     *
     * @return mixed
     */
    public function readDailyData($meterId, $date)
    {
        if ($this->api === null) {
            return;
        }
        if (substr_count($date, '-') !== 2) {
            throw new InvalidDateException($date, 'date is not in y-m-d format');
        }
        $dates = explode('-', $date);
        $body = $this->prepareDailyReq($meterId, $dates[0], $dates[1], $dates[2]);

        return $this->api->GetDataDaily($body)->GetDataDailyResult;
    }

    /**
     * Reads the total consumption of a meter.
     *
     * @param $meterId
     * @param $type
     *
     * @return void
     */
    public function readMeter($meterId, $type)
    {
    }

    /**
     * Reads the data for a given meter list.
     *
     * @param       $meterList
     * @param int   $type      defines what to read from the remote api
     * @param array $options
     *
     * @return void
     */
    public function readBatch($meterList, $type, $options)
    {
        if ($type === self::READ_DAILY) {
            if (!isset($options['date'])) {
                throw new InvalidDateException('Date', 'date is not set. Send date within the options array');
            }
            foreach ($meterList as $meter) {
                $data = $this->readDailyData($meter->serial_number, $options['date']);
                try {
                    $meterData = $this->fetchResponseData($data);

                    if (\count($meterData) !== 2) {
                        continue;
                    }
                    if ((int) $meterData[0] < 0) {
                        Log::critical(
                            'Meter Reading, negative consumption ',
                            ['meter_id' => $meter->id, 'consumption' => $meterData[0]]
                        );
                        continue;
                    }

                    $consumption = $this->consumption->create();
                    $consumption->meter_id = $meter->id;
                    $consumption->total_consumption = $meterData[0];
                    $consumption->credit_on_meter = $meterData[1];

                    $consumption->reading_date = $options['date'];
                    $this->calculateDifference($consumption)->save();
                } catch (MeterIsNotReadable $e) {
                    Log::debug(
                        'Meter is not readable',
                        ['meter' => $meter->serial_number, 'meter_id' => $meter->id]
                    );
                }
            }
        }
    }

    /**
     * Calculates the difference of usages with the prev. reading.
     *
     * @param MeterConsumption $consumption
     *
     * @return MeterConsumption
     */
    private function calculateDifference(MeterConsumption $consumption): MeterConsumption
    {
        $prevReading = $this->consumption->where('meter_id', $consumption->meter_id)->latest()->first();
        if ($prevReading !== null) {
            $consumption->consumption = $consumption->total_consumption - $prevReading->total_consumption;
        } else {
            $consumption->consumption = $consumption->total_consumption;
        }

        return $consumption;
    }

    /**
     * @param $data
     *
     * @return string[]
     *
     * @throws MeterIsNotReadable
     *
     * @psalm-return non-empty-list<string>
     */
    private function fetchResponseData($data): array
    {
        $out = [];
        /*fetch information out of the response.
        successful response : 00^total_power,rest_energy
        failed response : 05 */
        $regex = '/([0-9]{2})\^(.*)/';
        if (preg_match($regex, $data, $out) === 0) { // no match => failed response
            throw new MeterIsNotReadable($data);
        }

        return explode(',', $out[2]); // return the interesting data
    }

    /**
     * @param string $meterId
     * @param string $year
     * @param string $month
     * @param string $day
     * @param string $action
     *
     * @return array (\Illuminate\Config\Repository|float|mixed)[]
     */
    private function prepareDailyReq(string $meterId, string $year, string $month, string $day, $action = 'Read'): array
    {
        [$t1, $t2] = explode(' ', microtime());
        $timestamp = (float) sprintf('%.0f', ((float) $t1 + (float) $t2) * 1000);

        $params = [
            'userId' => \config('services.calin.meter.user'),
            'meterId' => $meterId,
            'dataWay' => $action,
            'timestamp' => $timestamp,
            'cipherText' => $this->generateCipher($meterId, $timestamp, $action),
            'year' => $year,
            'month' => $month,
            'day' => $day,
        ];

        return $params;
    }

    private function generateCipher($meterId, float $timestamp, $dataWay): string
    {
        return md5(
            sprintf(
                '%s%s%s%s%s',
                \config('services.calin.meter.user'),
                $meterId,
                $dataWay,
                $timestamp,
                \config('services.calin.meter.key')
            )
        );
    }
}
