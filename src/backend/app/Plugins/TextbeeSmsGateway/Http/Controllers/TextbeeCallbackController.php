<?php

namespace App\Plugins\TextbeeSmsGateway\Http\Controllers;

use App\Events\SmsStoredEvent;
use App\Models\Address\Address;
use App\Models\Sms;
use App\Plugins\TextbeeSmsGateway\Services\TextbeeCredentialService;
use App\Services\AddressesService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TextbeeCallbackController extends Controller {
    public function __construct(
        private TextbeeCredentialService $credentialService,
        private SmsService $smsService,
        private AddressesService $addressesService,
    ) {}

    public function incoming(Request $request, string $slug): JsonResponse {
        $credentials = $this->credentialService->getCredentials();

        if ($credentials?->webhook_secret) {
            $signature = $request->header('x-signature');
            $expectedSignature = hash_hmac('sha256', $request->getContent(), $credentials->webhook_secret);

            if (!$signature || !hash_equals($expectedSignature, $signature)) {
                return response()->json(['status' => 'unauthorized'], 401);
            }
        }

        $data = $request->all();
        $phoneNumber = $data['sender'];
        $message = $data['message'];
        $address = $this->addressesService->getAddressByPhoneNumber(str_replace(' ', '', $phoneNumber));
        $sender = $address instanceof Address ? $address->owner : null;
        $senderId = $sender?->getKey();

        $smsData = [
            'receiver' => $address->phone ?? $phoneNumber,
            'body' => $message,
            'sender_id' => $senderId,
            'direction' => Sms::DIRECTION_INCOMING,
            'status' => Sms::STATUS_DELIVERED,
        ];

        $sms = $this->smsService->createSms($smsData);
        event(new SmsStoredEvent($phoneNumber, $message, $sms));

        return response()->json(['status' => 'success']);
    }
}
