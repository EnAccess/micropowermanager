<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 09.10.18
 * Time: 19:08.
 */

namespace App\Http\Controllers;

use App\Http\Requests\SmsRequest;
use App\Http\Requests\StoreSmsRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\SmsSearchResultResource;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inensus\Ticket\Services\TicketCommentService;

class SmsController extends Controller
{
    public function __construct(
        private Sms $sms,
        private Person $person,
        private MeterParameter $meterParameter,
        private SmsService $smsService,
        private TicketCommentService $commentService
    ) {
    }

    public function index(): ApiResource
    {
        $list = $this->sms::with('address.owner')
            ->orderBy('id', 'DESC')
            ->select('receiver', DB::raw('count(*) as total'))
            ->groupBy('receiver')
            ->paginate(20);

        return new ApiResource($list);
    }

    /**
     * @return void
     */
    public function storeBulk(Request $request)
    {
        $type = $request->get('type');
        $receivers = $request->get('receivers');
        $message = $request->get('message');
        $miniGrid = $request->get('miniGrid') ?? 0;
        $senderId = $request->get('senderId');
        if ($type === null) {
            return;
        }
        if ($type === 'person') {
            foreach ($receivers as $receiver) {
                $phone = $receiver;
                $smsData = [
                    'receiver' => $phone,
                    'body' => $message,
                    'direction' => 1,
                    'sender_id' => $senderId,
                ];
                $this->smsService->createSms($smsData);
                $data = [
                    'message' => $message,
                    'phone' => $phone,
                ];
                $this->smsService->sendSms($data, SmsTypes::MANUAL_SMS, SmsConfigs::class);
            }
        } elseif ($type === 'group' || $type === 'type' || $type === 'all') {
            // get connection group meters and owners
            if ($type === 'group') {
                $meters = $this->meterParameter::with(
                    [
                        'owner.addresses' => static function ($q) {
                            $q->where('is_primary', 1);
                        },
                    ]
                )
                    ->whereHas(
                        'address',
                        static function ($q) use ($miniGrid) {
                            if ((int) $miniGrid === 0) {
                                $q->where('city_id', '>', 0);
                            } else {
                                $q->where('city_id', $miniGrid);
                            }
                        }
                    )->whereHas(
                        'connectionGroup',
                        function ($q) use ($receivers) {
                            $q->where('id', $receivers);
                        }
                    )->get();
            } elseif ($type === 'all') {
                $meters = $this->meterParameter::with(
                    [
                        'owner.addresses' => static function ($q) {
                            $q->where('is_primary', 1);
                        },
                    ]
                )
                    ->whereHas(
                        'address',
                        static function ($q) use ($miniGrid) {
                            if ((int) $miniGrid === 0) {
                                $q->where('city_id', '>', 0);
                            } else {
                                $q->where('city_id', $miniGrid);
                            }
                        }
                    )->get();
            } else {
                $meters = $this->meterParameter::with(
                    [
                        'owner.addresses' => static function ($q) {
                            $q->where('is_primary', 1);
                        },
                    ]
                )
                    ->whereHas(
                        'address',
                        static function ($q) use ($miniGrid) {
                            if ((int) $miniGrid === 0) {
                                $q->where('city_id', '>', 0);
                            } else {
                                $q->where('city_id', $miniGrid);
                            }
                        }
                    )->whereHas(
                        'connectionType',
                        function ($q) use ($receivers) {
                            $q->where('id', $receivers);
                        }
                    )->get();
            }

            $addresses = $meters->pluck('owner.addresses');
            foreach ($addresses as $address) {
                if ($address === null) {
                    continue;
                }
                $this->sms->newQuery()->create(
                    [
                        'receiver' => $address[0]->phone,
                        'body' => $message,
                        'direction' => 1,
                        'sender_id' => $senderId,
                    ]
                );
                $data = [
                    'message' => $message,
                    'phone' => $address[0]->phone,
                ];
                $this->smsService->sendSms($data, SmsTypes::MANUAL_SMS, SmsConfigs::class);
            }
        }
    }

    public function store(StoreSmsRequest $request): ApiResource
    {
        $sender = $request->get('sender');
        $message = $request->get('message');
        $smsData = [
            'receiver' => $sender,
            'body' => $message,
            'direction' => 0,
            'sender_id' => null,
        ];
        $sms = $this->smsService->createSms($smsData);

        switch ($this->smsService->checkMessageType($message)) {
            case $this->smsService::FEEDBACK:
                event('sms.stored', [$sender, $message]);
                break;
            case $this->smsService::TICKET:
                $this->commentService->storeComment($sender, $message);
                break;
        }

        return new ApiResource($sms);
    }

    public function storeAndSend(SmsRequest $request): ApiResource
    {
        $personId = $request->get('person_id');
        $message = $request->get('message');
        $senderId = $request->get('senderId');
        if ($personId !== null) {
            // get person primary phone
            $primaryAddress = $this->person::with('addresses')
                ->whereHas(
                    'addresses',
                    static function ($q) {
                        $q->where('is_primary', 1);
                    }
                )
                ->find($personId);
            $phone = $primaryAddress->addresses[0]->phone;
        } else {
            $phone = $request->get('phone');
        }

        $smsData = [
            'receiver' => $phone,
            'body' => $message,
            // TODO MPM-78
            // 'direction' => SmsService::DIRECTION_OUTGOING,
            'sender_id' => $senderId,
        ];
        $sms = $this->smsService->createAndSendSms($smsData);

        return new ApiResource($sms);
    }

    /**
     * Marks the sms as sent.
     *
     * @param string $uuid
     *
     * @return void
     */
    public function updateForDelivered($uuid): void
    {
        try {
            Log::info('Sms has delivered successfully', ['uuid' => $uuid]);
            $sms = $this->sms->where('uuid', $uuid)->firstOrFail();
            $sms->status = 2;
            $sms->save();
        } catch (ModelNotFoundException $e) {
            Log::critical(
                'Sms confirmation update failed ',
                [
                    'uuid' => $uuid,
                    'message' => 'the given uuid is not found in the database',
                ]
            );
        }
    }

    public function updateForFailed($uuid): void
    {
        try {
            Log::warning('Sending Sms failed on AndroidGateway', ['uuid' => $uuid]);
            $sms = $this->sms->where('uuid', $uuid)->firstOrFail();
            $sms->status = -1;
            $sms->save();
        } catch (ModelNotFoundException $e) {
            Log::critical(
                'Sms rejection update failed ',
                [
                    'uuid' => $uuid,
                    'message' => 'the given uuid is not found in the database',
                ]
            );
        }
    }

    public function updateForSent($uuid): void
    {
        try {
            Log::warning('Sms has sent successfully', ['uuid' => $uuid]);
            $sms = $this->sms->where('uuid', $uuid)->firstOrFail();
            $sms->status = 1;
            $sms->save();
        } catch (ModelNotFoundException $e) {
            Log::critical(
                'Sms rejection update failed ',
                [
                    'uuid' => $uuid,
                    'message' => 'the given uuid is not found in the database',
                ]
            );
        }
    }

    public function show($person_id): ApiResource
    {
        $personAddresses = $this->person::with(
            [
                'addresses' => function ($q) {
                    $q->select(DB::raw('phone'), 'owner_id');
                },
            ]
        )
            ->where('id', $person_id)
            ->first();
        $numbers = $personAddresses->addresses->toArray();
        $smses = $this->sms::whereIn('receiver', $numbers)->orderBy('id', 'ASC')->get();

        return new ApiResource($smses);
    }

    public function byPhone($phone): ApiResource
    {
        $smses = $this->sms->where('receiver', $phone)->get();

        return new ApiResource($smses);
    }

    public function search($search)
    {
        // search in people
        $list = $this->person::with('addresses')
            ->whereHas(
                'addresses',
                function ($q) use ($search) {
                    $q->where('phone', 'like', '%'.$search.'%')
                        ->where('is_primary', 1);
                }
            )
            ->orWhere('name', 'like', '%'.$search.'%')
            ->orWhere('surname', 'like', '%'.$search.'%')
            ->get();

        return SmsSearchResultResource::collection($list);
    }
}
