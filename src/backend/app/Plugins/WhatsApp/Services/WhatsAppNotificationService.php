<?php

namespace App\Plugins\WhatsApp\Services;

use App\Models\ApplianceRate;
use App\Models\Device;
use App\Models\MainSettings;
use App\Models\Person\Person;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\WhatsApp\Exceptions\WhatsAppApiException;
use App\Plugins\WhatsApp\Jobs\SendWhatsAppMessageJob;
use App\Plugins\WhatsApp\Models\WhatsAppCredential;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use App\Plugins\WhatsApp\Models\WhatsAppTemplate;

/**
 * Entry point for every transactional WhatsApp notification.
 *
 * Callers describe the business event (a receipt, a token, a low balance) and
 * this service decides whether WhatsApp is enabled for that event, resolves the
 * recipient and template, and queues a single message. It never invents domain
 * data — amounts, tokens and balances always come from the MPM models passed in.
 */
class WhatsAppNotificationService {
    public function __construct(
        private WhatsAppCredentialService $credentialService,
        private WhatsAppMessageService $messageService,
        private WhatsAppTemplateVariableResolver $variableResolver,
    ) {}

    /**
     * Whether WhatsApp currently owns the given notification. The parallel SMS
     * paths consult this so a channel change never sends both messages.
     */
    public function isEnabledFor(string $notificationType): bool {
        $credentials = $this->credentialService->getCredentials();

        return $credentials instanceof WhatsAppCredential
            && $credentials->is_enabled
            && $credentials->isNotificationEnabled($notificationType);
    }

    public function sendPaymentReceipt(Transaction $transaction): ?WhatsAppMessage {
        $person = $this->resolvePerson($transaction);

        return $this->dispatch(
            WhatsAppMessage::TYPE_PAYMENT_RECEIPT,
            'transaction:'.$transaction->id,
            $this->paymentVariables($transaction, $person),
            $transaction,
            $person,
        );
    }

    public function sendTokenDelivery(Transaction $transaction): ?WhatsAppMessage {
        $token = $transaction->token;

        if (!$token instanceof Token) {
            return null;
        }

        $person = $this->resolvePerson($transaction);

        return $this->dispatch(
            WhatsAppMessage::TYPE_TOKEN_DELIVERY,
            'transaction:'.$transaction->id,
            $this->tokenVariables($transaction, $token, $person),
            $transaction,
            $person,
        );
    }

    public function sendLowBalanceWarning(ApplianceRate $applianceRate, bool $overdue): ?WhatsAppMessage {
        $person = $applianceRate->appliancePerson?->person;
        $reminderKind = $overdue ? 'overdue' : 'upcoming';

        return $this->dispatch(
            WhatsAppMessage::TYPE_LOW_BALANCE_WARNING,
            'appliance_rate:'.$applianceRate->id.':'.$reminderKind,
            $this->lowBalanceVariables($applianceRate, $person),
            null,
            $person,
        );
    }

    /**
     * @throws WhatsAppApiException
     */
    public function sendTestMessage(
        string $recipientPhone,
        ?int $templateId = null,
        ?string $messageText = null,
    ): WhatsAppMessage {
        $phone = $this->normalizePhone($recipientPhone);

        if ($phone === null) {
            throw new WhatsAppApiException('Invalid recipient phone number.');
        }

        $template = null;
        if ($templateId !== null) {
            $template = WhatsAppTemplate::query()->find($templateId);

            if (! $template instanceof WhatsAppTemplate) {
                throw new WhatsAppApiException('Selected WhatsApp template does not exist.');
            }
        }

        $rawBody = $template instanceof WhatsAppTemplate
            ? $template->body
            : ($messageText ?: 'Hello from MicroPowerManager. This is a test WhatsApp message.');

        $sampleVariables = [
            'customer_name' => 'John Doe',
            'surname' => 'Doe',
            'token' => '4829-1094-8832',
            'token_amount' => '15.5 kWh',
            'payment_amount' => '5,000',
            'currency' => 'TZS',
            'payment_date' => now()->format('Y-m-d'),
            'transaction_id' => 'TXN-'.rand(100000, 999999),
            'balance' => '2.4 kWh',
            'device_serial' => 'MTR-98214',
            'due_date' => now()->addDays(3)->format('Y-m-d'),
        ];

        $body = $this->variableResolver->resolve($rawBody, $sampleVariables);

        $message = $this->messageService->queue([
            'notification_type' => WhatsAppMessage::TYPE_TEST,
            'recipient_phone' => $phone,
            'recipient_name' => $sampleVariables['customer_name'],
            'body' => $body,
            'template_id' => $template?->id,
        ]);

        $this->messageService->deliver($message);

        return $message;
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function dispatch(
        string $notificationType,
        ?string $reference,
        array $variables,
        ?Transaction $transaction,
        ?Person $person,
    ): ?WhatsAppMessage {
        $credentials = $this->credentialService->getCredentials();

        if (!$credentials instanceof WhatsAppCredential || !$credentials->is_enabled) {
            return null;
        }

        if (!$credentials->isNotificationEnabled($notificationType)) {
            return null;
        }

        if ($reference !== null && $this->messageService->hasActiveMessage($notificationType, $reference)) {
            return null;
        }

        $template = WhatsAppTemplate::activeFor($notificationType);
        $templateBody = $template instanceof WhatsAppTemplate
            ? $template->body
            : WhatsAppTemplate::defaultBodyFor($notificationType);
        $body = $this->variableResolver->resolve($templateBody, $variables);

        $attributes = [
            'notification_type' => $notificationType,
            'reference' => $reference,
            'recipient_phone' => '',
            'recipient_name' => $variables['customer_name'] ?? null,
            'transaction_id' => $transaction?->id,
            'person_id' => $person?->id,
            'template_id' => $template?->id,
            'body' => $body,
        ];

        $phone = $this->resolvePhone($transaction, $person);

        if ($phone === null) {
            // The customer cannot be reached; still record the attempt so the
            // failure is visible in the message history.
            $attributes['recipient_phone'] = $transaction instanceof Transaction ? (string) $transaction->sender : '';
            $this->messageService->recordFailure($attributes, 'Customer has no valid WhatsApp phone number.');

            return null;
        }

        $attributes['recipient_phone'] = $phone;
        $message = $this->messageService->queue($attributes);

        dispatch(new SendWhatsAppMessageJob($message->id));

        return $message;
    }

    private function resolvePerson(Transaction $transaction): ?Person {
        foreach ([$transaction->device, $transaction->paygoAppliance, $transaction->nonPaygoAppliance] as $related) {
            if ($related?->person instanceof Person) {
                return $related->person;
            }
        }

        return null;
    }

    private function resolvePhone(?Transaction $transaction, ?Person $person): ?string {
        $sender = $transaction?->sender;

        if (is_string($sender) && !in_array($sender, ['', '-'], true)) {
            $normalizedSender = $this->normalizePhone($sender);

            if ($normalizedSender !== null) {
                return $normalizedSender;
            }
        }

        $address = $person?->addresses()->where('is_primary', 1)->first();

        return $this->normalizePhone($address?->phone);
    }

    private function normalizePhone(?string $phone): ?string {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (!is_string($digits) || strlen($digits) < 9 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }

    /**
     * @return array<string, string>
     */
    private function paymentVariables(Transaction $transaction, ?Person $person): array {
        $token = $transaction->token;

        return [
            'customer_name' => $this->customerName($person),
            'surname' => $person instanceof Person ? (string) $person->surname : '',
            'payment_amount' => number_format((float) $transaction->amount, 2, '.', ''),
            'payment_date' => ($transaction->created_at ?? now())->toDateString(),
            'transaction_id' => (string) $transaction->id,
            'token' => $token instanceof Token ? $token->token : '',
            'currency' => $this->currency(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function tokenVariables(Transaction $transaction, Token $token, ?Person $person): array {
        return [
            'customer_name' => $this->customerName($person),
            'surname' => $person instanceof Person ? (string) $person->surname : '',
            'token' => (string) $token->token,
            'token_amount' => (string) $token->token_amount,
            'payment_amount' => number_format((float) $transaction->amount, 2, '.', ''),
            'transaction_id' => (string) $transaction->id,
            'device_serial' => $token->device instanceof Device ? (string) $token->device->device_serial : (string) $transaction->message,
            'currency' => $this->currency(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function lowBalanceVariables(ApplianceRate $applianceRate, ?Person $person): array {
        return [
            'customer_name' => $this->customerName($person),
            'surname' => $person instanceof Person ? (string) $person->surname : '',
            'balance' => number_format((float) $applianceRate->remaining, 2, '.', ''),
            'due_date' => (string) $applianceRate->due_date,
            'currency' => $this->currency(),
        ];
    }

    private function customerName(?Person $person): string {
        if (!$person instanceof Person) {
            return '';
        }

        return trim($person->name.' '.$person->surname);
    }

    private function currency(): string {
        return (string) (MainSettings::query()->value('currency') ?? '');
    }
}
