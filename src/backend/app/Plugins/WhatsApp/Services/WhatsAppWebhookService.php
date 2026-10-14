<?php

namespace App\Plugins\WhatsApp\Services;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WhatsAppWebhookService {
    public function validateAuthenticationFields(Request $request): void {
        if ($request->isMethod('GET')) {
            $parameters = $this->verificationParameters($request);
            if ($parameters['hub.mode'] !== 'subscribe') {
                throw new HttpException(403, 'Webhook request rejected.');
            }

            return;
        }
        if (!preg_match('/^sha256=[a-fA-F0-9]{64}$/D', $request->header('X-Hub-Signature-256', ''))) {
            throw new HttpException(403, 'Webhook request rejected.');
        }
    }

    /** @param array{secret: string, account: string, phone: string} $context */
    public function authenticate(Request $request, #[\SensitiveParameter] array $context): void {
        if ($request->isMethod('GET')) {
            $parameters = $this->verificationParameters($request);
            if (!hash_equals($context['secret'], $parameters['hub.verify_token'])) {
                throw new HttpException(403, 'Webhook request rejected.');
            }
            $request->attributes->set('whatsapp.challenge', $parameters['hub.challenge']);

            return;
        }
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $context['secret']);
        if (!hash_equals($expected, strtolower($request->header('X-Hub-Signature-256', '')))) {
            throw new HttpException(403, 'Webhook request rejected.');
        }
        $payload = json_decode($request->getContent(), true, 64);
        if (!is_array($payload) || ($payload['object'] ?? null) !== 'whatsapp_business_account'
            || !$this->isNonemptyList($payload['entry'] ?? null)) {
            throw new HttpException(400, 'Invalid webhook payload.');
        }
        $pending = false;
        foreach ($payload['entry'] as $entry) {
            if (!is_array($entry) || !is_string($entry['id'] ?? null) || $entry['id'] !== $context['account']) {
                throw new HttpException(403, 'Webhook request rejected.');
            }
            if (!$this->isNonemptyList($entry['changes'] ?? null)) {
                throw new HttpException(400, 'Invalid webhook payload.');
            }
            foreach ($entry['changes'] as $change) {
                if (!is_array($change) || !is_string($change['field'] ?? null) || $change['field'] === '' || !is_array($change['value'] ?? null)) {
                    throw new HttpException(400, 'Invalid webhook payload.');
                }
                if ($change['field'] !== 'messages') {
                    continue;
                }
                $value = $change['value'];
                if (($value['messaging_product'] ?? null) !== 'whatsapp' || !is_array($value['metadata'] ?? null)
                    || !is_string($value['metadata']['phone_number_id'] ?? null)) {
                    throw new HttpException(400, 'Invalid webhook payload.');
                }
                if ($value['metadata']['phone_number_id'] !== $context['phone']) {
                    throw new HttpException(403, 'Webhook request rejected.');
                }
                $hasEvents = false;
                foreach (['statuses', 'messages', 'errors'] as $field) {
                    if (!array_key_exists($field, $value)) {
                        continue;
                    }
                    if (!$this->isNonemptyList($value[$field])) {
                        throw new HttpException(400, 'Invalid webhook payload.');
                    }
                    foreach ($value[$field] as $event) {
                        if (!is_array($event)) {
                            throw new HttpException(400, 'Invalid webhook payload.');
                        }
                        if ($field === 'statuses' && (!is_string($event['id'] ?? null) || $event['id'] === ''
                            || !is_string($event['status'] ?? null) || $event['status'] === ''
                            || !is_string($event['timestamp'] ?? null) || !ctype_digit($event['timestamp']))) {
                            throw new HttpException(400, 'Invalid webhook payload.');
                        }
                    }
                    $hasEvents = true;
                }
                if (!$hasEvents) {
                    throw new HttpException(400, 'Invalid webhook payload.');
                }
                $pending = true;
            }
        }
        $request->attributes->set('whatsapp.processing_pending', $pending);
    }

    /** @return array<string, string> */
    private function verificationParameters(Request $request): array {
        // Read the wire query so PHP's dot-to-underscore normalization cannot hide duplicates.
        $parameters = [];
        foreach (explode('&', $request->server->get('QUERY_STRING', '')) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $key = urldecode($key);
            if (!in_array($key, ['hub.mode', 'hub.verify_token', 'hub.challenge'], true)) {
                throw new HttpException(403, 'Webhook request rejected.');
            }
            if (array_key_exists($key, $parameters)) {
                throw new HttpException(403, 'Webhook request rejected.');
            }
            $parameters[$key] = urldecode($value);
        }
        foreach (['hub.mode', 'hub.verify_token', 'hub.challenge'] as $key) {
            if (!isset($parameters[$key]) || $parameters[$key] === '' || strlen($parameters[$key]) > 4096) {
                throw new HttpException(403, 'Webhook request rejected.');
            }
        }

        return $parameters;
    }

    private function isNonemptyList(mixed $value): bool {
        return is_array($value) && array_is_list($value) && $value !== [];
    }
}
