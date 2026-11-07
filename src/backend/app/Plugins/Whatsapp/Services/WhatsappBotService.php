<?php

namespace App\Plugins\Whatsapp\Services;

use App\Models\AppliancePerson;
use App\Plugins\Whatsapp\Models\WhatsappInboundMessage;
use App\Services\AppliancePersonService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * Handles messages a customer sends to the business number.
 * Stateless: each message is matched by keyword, anything else shows the menu.
 */
class WhatsappBotService {
    public function __construct(
        private WhatsappCredentialService $credentialService,
        private WhatsappClient $client,
        private WhatsappOptInService $optInService,
        private AppliancePersonService $appliancePersonService,
        private WhatsappInboundMessage $inbound,
    ) {}

    /**
     * @param array<string, mixed> $message one entry of Meta's "messages" array
     */
    public function handleInbound(array $message): void {
        $wamid = $message['id'] ?? null;
        $from = preg_replace('/\D/', '', (string) ($message['from'] ?? ''));
        if (!$wamid || $from === '') {
            return;
        }

        $text = $this->extractText($message);

        // Meta retries deliveries, so every message id is processed only once
        try {
            $this->inbound->newQuery()->create(['wamid' => $wamid, 'phone' => $from, 'body' => $text]);
        } catch (UniqueConstraintViolationException) {
            return;
        }

        $command = strtolower(trim($text));

        $reply = match (true) {
            in_array($command, ['1', 'balance'], true) => $this->balance($from),
            in_array($command, ['2', 'start', 'subscribe'], true) => $this->optIn($from),
            in_array($command, ['3', 'stop', 'unsubscribe'], true) => $this->optOut($from),
            default => $this->menu(),
        };

        $this->reply($from, $reply);
    }

    /**
     * @param array<string, mixed> $message
     */
    private function extractText(array $message): string {
        return (string) match ($message['type'] ?? '') {
            'text' => $message['text']['body'] ?? '',
            'interactive' => $message['interactive']['button_reply']['id']
                ?? $message['interactive']['list_reply']['id'] ?? '',
            'button' => $message['button']['text'] ?? '',
            default => '',
        };
    }

    private function menu(): string {
        return "Hello! This is MicroPowerManager. Reply with a number:\n"
            ."1 - Check my balance\n"
            ."2 - Get payment notifications on WhatsApp\n"
            .'3 - Stop WhatsApp notifications';
    }

    private function balance(string $from): string {
        try {
            $phone = phone('+'.$from)->formatE164();
        } catch (\Throwable) {
            $phone = '+'.$from;
        }

        $accounts = $this->appliancePersonService->getPayableByPhone($phone);
        if ($accounts->isEmpty()) {
            return 'We could not find an account linked to this number. Please contact your provider.';
        }

        $lines = $accounts->map(function (AppliancePerson $account): string {
            $name = $account->appliance->name ?? 'Appliance';
            $detail = $account->isEnergyService()
                ? 'pay-as-you-go'
                : number_format((float) $account->rates->sum('remaining'), 0).' remaining';

            return "- {$name}: {$detail}";
        });

        return "Your accounts:\n".$lines->implode("\n")."\nReply 0 for the menu.";
    }

    private function optIn(string $from): string {
        $this->optInService->optIn($from, 'inbound_keyword');

        return 'You will now receive payment confirmations and reminders here. Reply 3 to stop at any time.';
    }

    private function optOut(string $from): string {
        $this->optInService->optOut($from);

        return 'You will no longer receive WhatsApp notifications. Reply 2 to start again.';
    }

    private function reply(string $to, string $body): void {
        $credentials = $this->credentialService->getCredentials();
        if (!$credentials || !$credentials->access_token || !$credentials->phone_number_id) {
            return;
        }

        $result = $this->client->sendText($credentials, $to, $body);
        if (!$result['ok']) {
            Log::warning('WhatsApp bot reply failed', ['to' => $to, 'error' => $result['error']]);
        }
    }
}
