<?php

namespace App\Plugins\AfricasTalking\Http\Controllers;

use App\Events\SmsStoredEvent;
use App\Models\Address\Address;
use App\Models\Sms;
use App\Plugins\AfricasTalking\Services\AfricasTalkingMessageService;
use App\Services\AddressesService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AfricasTalkingCallbackController extends Controller {
    public function __construct(
        private AfricasTalkingMessageService $messageService,
        private SmsService $smsService,
        private AddressesService $addressesService,
    ) {}

    public function incoming(Request $request): JsonResponse {
        $data = $request->all();
        $phoneNumber = $data['from'];
        $message = $data['text'];
        $address = $this->addressesService->getAddressByPhoneNumber(str_replace(' ', '', $phoneNumber));
        $sender = $address instanceof Address ? $address->owner : null;
        // @phpstan-ignore property.notFound
        $senderId = $sender ? $sender->id : null;

        $smsData = [
            'receiver' => $phoneNumber,
            'body' => $message,
            'sender_id' => $senderId,
            'direction' => Sms::DIRECTION_INCOMING,
            'status' => Sms::STATUS_DELIVERED,
        ];

        $this->smsService->createSms($smsData);
        event(new SmsStoredEvent($phoneNumber, $message));

        return response()->json(['status' => 'success']);
    }

    public function delivery(Request $request): JsonResponse {
        $data = $request->all();
        $status = $data['status'];
        $id = $data['id'];
        $africasTalkingMessage = $this->messageService->getByMessageId($id);

        $africasTalkingMessage->update([
            'status' => $status,
        ]);

        $africasTalkingMessage->sms()->update([
            'status' => $status === 'Success'
                ? Sms::STATUS_DELIVERED
                : ($status === 'Rejected' || $status === 'Failed'
                    ? Sms::STATUS_FAILED
                    : Sms::STATUS_STORED),
        ]);

        return response()->json(['status' => 'success']);
    }
}
