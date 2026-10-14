<?php

namespace App\Plugins\WhatsApp\Http\Controllers;

use App\Plugins\WhatsApp\Services\WhatsAppDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WhatsAppWebhookController extends Controller {
    public function __construct(private WhatsAppDeliveryService $delivery) {}

    public function verify(Request $request): Response {
        $challenge = $request->attributes->get('whatsapp.challenge');
        if (!is_string($challenge)) {
            throw new HttpException(403, 'Webhook request rejected.');
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request): Response {
        $pending = $request->attributes->get('whatsapp.processing_pending');
        $statuses = $request->attributes->get('whatsapp.delivery_statuses');
        $companyId = $request->attributes->get('companyId');
        if (!is_bool($pending) || !is_array($statuses) || !is_int($companyId)) {
            throw new HttpException(403, 'Webhook request rejected.');
        }
        if ($statuses !== []) {
            try {
                $this->delivery->accept($companyId, $statuses);
            } catch (\Throwable) {
                throw new HttpException(503, 'Webhook processing unavailable.', null, ['Retry-After' => '60']);
            }
        }
        if ($pending) {
            throw new HttpException(503, 'Webhook processing unavailable.', null, ['Retry-After' => '60']);
        }

        return response('', 200);
    }
}
