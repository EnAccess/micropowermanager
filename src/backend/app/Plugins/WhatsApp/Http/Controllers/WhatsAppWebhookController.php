<?php

namespace App\Plugins\WhatsApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WhatsAppWebhookController extends Controller {
    public function verify(Request $request): Response {
        $challenge = $request->attributes->get('whatsapp.challenge');
        if (!is_string($challenge)) {
            throw new HttpException(403, 'Webhook request rejected.');
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request): Response {
        $pending = $request->attributes->get('whatsapp.processing_pending');
        if (!is_bool($pending)) {
            throw new HttpException(403, 'Webhook request rejected.');
        }
        if ($pending) {
            throw new HttpException(503, 'Webhook processing unavailable.', null, ['Retry-After' => '60']);
        }

        return response('', 200);
    }
}
