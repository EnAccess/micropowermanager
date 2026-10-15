<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Listeners;

use App\Events\TransactionFailedEvent;
use App\Models\Address\Address;
use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Models\Transaction\Transaction;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptIn;
use App\Plugins\WhatsappBusinessApi\Services\WhatsappCredentialService;
use App\Plugins\WhatsappBusinessApi\WhatsappGateway;
use App\Services\MainSettingsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransactionFailedWhatsappListener {
    public function __construct(
        private WhatsappGateway $whatsappGateway,
        private WhatsappCredentialService $credentialService,
        private MainSettingsService $mainSettingsService,
        private \App\Services\PluginsService $pluginsService,
    ) {}

    public function handle(TransactionFailedEvent $event): void {
        try {
            if (!$this->pluginsService->isPluginActive(MpmPlugin::WHATSAPP_BUSINESS_API)) {
                return;
            }

            $credentials = $this->credentialService->getCredentials();
            if (!$credentials || empty($credentials->access_token) || empty($credentials->phone_number_id)) {
                return;
            }

            $transaction = $event->transaction;
            $person = $this->resolvePerson($transaction);
            if (!$person instanceof Person) {
                Log::info('Could not resolve customer for failed transaction WhatsApp notification', [
                    'transaction_id' => $transaction->id,
                ]);

                return;
            }

            $optIn = WhatsappBusinessOptIn::query()->where('person_id', $person->id)->first();
            if (!$optIn || !$optIn->opted_in) {
                Log::info('Customer has not opted in for WhatsApp messages. Skipping failure notification.', [
                    'person_id' => $person->id,
                    'transaction_id' => $transaction->id,
                ]);

                return;
            }

            $receiver = $this->resolvePhoneNumber($person, $transaction);
            if (empty($receiver)) {
                Log::warning('No valid phone number for customer on failed transaction notification', [
                    'person_id' => $person->id,
                    'transaction_id' => $transaction->id,
                ]);

                return;
            }

            $currency = $this->mainSettingsService->getAll()->first()->currency ?? 'USD';
            $amount = $transaction->amount.' '.$currency;
            $reason = !empty($event->message) ? $event->message : 'Payment or token generation could not be processed';

            $body = sprintf(
                'Dear %s, your payment of %s could not be processed. Reason: %s. Please contact customer care for assistance.',
                $person->name,
                $amount,
                $reason
            );

            $messageId = $this->whatsappGateway->sendTextMessage($receiver, $body);

            Sms::query()->create([
                'receiver' => $receiver,
                'body' => $body,
                'status' => $messageId ? Sms::STATUS_SENT : Sms::STATUS_FAILED,
                'direction' => Sms::DIRECTION_OUTGOING,
                'trigger_type' => Transaction::RELATION_NAME,
                'trigger_id' => $transaction->id,
                'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
                'uuid' => substr((string) ($messageId ?? Str::uuid()->toString()), 0, 191),
                'error_message' => $messageId ? null : 'Failed to dispatch via WhatsApp Cloud API',
            ]);

            Log::info('Dispatched failed transaction notification via WhatsApp', [
                'transaction_id' => $transaction->id,
                'person_id' => $person->id,
                'receiver' => $receiver,
                'message_id' => $messageId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error dispatching WhatsApp failed transaction notification', [
                'transaction_id' => $event->transaction->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolvePerson(Transaction $transaction): ?Person {
        $device = $transaction->device;
        if ($device) {
            if ($device->person) {
                return $device->person;
            }
            if (!empty($device->person_id)) {
                return Person::query()->find($device->person_id);
            }
        }

        $paygo = $transaction->paygoAppliance;
        if ($paygo) {
            if ($paygo->person) {
                return $paygo->person;
            }
            if (!empty($paygo->person_id)) {
                return Person::query()->find($paygo->person_id);
            }
        }

        $nonPaygo = $transaction->nonPaygoAppliance;
        if ($nonPaygo) {
            if ($nonPaygo->person) {
                return $nonPaygo->person;
            }
            if (!empty($nonPaygo->person_id)) {
                return Person::query()->find($nonPaygo->person_id);
            }
        }

        if (!empty($transaction->sender)) {
            $sender = (string) $transaction->sender;
            $clean = preg_replace('/[^0-9]/', '', $sender) ?? '';
            $candidates = array_unique(array_filter([
                $sender,
                '+'.$clean,
                $clean,
                ltrim($clean, '0'),
                '0'.ltrim($clean, '0'),
            ]));

            $address = Address::query()->whereIn('owner_type', ['person', Person::class])
                ->where(function ($query) use ($candidates, $clean): void {
                    $query->whereIn('phone', $candidates);
                    if ($clean !== '') {
                        $query->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') = ?", [$clean]);
                    }
                })
                ->first();

            if ($address && $address->owner instanceof Person) {
                return $address->owner;
            }
            if ($address && !empty($address->owner_id)) {
                return Person::query()->find($address->owner_id);
            }
        }

        return null;
    }

    private function resolvePhoneNumber(Person $person, Transaction $transaction): ?string {
        $primaryAddress = $person->addresses()->where('is_primary', 1)->first();
        if ($primaryAddress && !empty($primaryAddress->phone)) {
            return (string) $primaryAddress->phone;
        }

        $anyAddress = $person->addresses()->first();
        if ($anyAddress && !empty($anyAddress->phone)) {
            return (string) $anyAddress->phone;
        }

        if (!empty($transaction->sender)) {
            return (string) $transaction->sender;
        }

        return null;
    }
}
