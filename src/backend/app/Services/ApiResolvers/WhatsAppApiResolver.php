<?php

declare(strict_types=1);

namespace App\Services\ApiResolvers;

use App\Plugins\WhatsApp\Services\WhatsAppWebhookCredentialReader;
use App\Plugins\WhatsApp\Services\WhatsAppWebhookService;
use App\Services\Interfaces\IApiResolver;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WhatsAppApiResolver implements IApiResolver {
    public function __construct(
        private WhatsAppWebhookCredentialReader $credentials,
        private WhatsAppWebhookService $webhooks,
        private RateLimiter $limiter,
    ) {}

    public function resolveCompanyId(Request $request): int {
        if (!config('whatsapp.webhooks_enabled') || !app()->environment('local', 'development', 'testing')) {
            throw new HttpException(503, 'Webhook processing unavailable.');
        }
        if (!preg_match('~^api/whatsapp/webhook/([1-9][0-9]{0,9})$~D', $request->path(), $matches)
            || !in_array($request->method(), ['GET', 'POST'], true) || (int) $matches[1] > 4294967295) {
            throw new HttpException(403, 'Webhook request rejected.');
        }
        $limitKey = 'whatsapp-webhook:'.hash('sha256', $request->ip() ?? 'unknown');
        if ($this->limiter->tooManyAttempts($limitKey, (int) config('whatsapp.webhook_requests_per_minute'))) {
            throw new HttpException(429, 'Webhook request rejected.', null, ['Retry-After' => '60']);
        }
        $this->limiter->hit($limitKey, 60);
        if (strlen($request->getContent()) > (int) config('whatsapp.webhook_max_bytes') || strlen((string) $request->server->get('QUERY_STRING', '')) > 8192) {
            throw new HttpException(413, 'Webhook request rejected.');
        }
        $verification = $request->isMethod('GET');
        $this->webhooks->validateAuthenticationFields($request);
        $companyId = (int) $matches[1];
        $context = $this->credentials->read($companyId, $verification);
        $this->webhooks->authenticate($request, $context);

        return $companyId;
    }
}
