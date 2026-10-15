<?php

namespace App\Plugins\Whatsapp\Services;

use App\Plugins\Whatsapp\Models\WhatsappCredential;
use Illuminate\Support\Facades\Http;

class WhatsappClient {
    private function base(WhatsappCredential $c): string {
        $root = env('WHATSAPP_GRAPH_BASE_URL', 'https://graph.facebook.com');

        return "{$root}/{$c->graph_version}";
    }

    /**
     * @param array<int, string> $params
     *
     * @return array{ok: bool, message_id: ?string, error: ?string}
     */
    public function sendTemplate(WhatsappCredential $c, string $to, string $template, string $lang, array $params): array {
        $clean = array_map(fn ($p) => trim(preg_replace('/\s+/', ' ', (string) $p)), $params);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $lang],
                'components' => $clean ? [[
                    'type' => 'body',
                    'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => $p], $clean),
                ]] : [],
            ],
        ];

        $res = Http::withToken($c->access_token)->timeout(15)
            ->post("{$this->base($c)}/{$c->phone_number_id}/messages", $payload);

        return [
            'ok' => $res->successful(),
            'message_id' => $res->json('messages.0.id'),
            'error' => $res->successful() ? null
                : '(#'.$res->json('error.code').') '.($res->json('error.message') ?? 'HTTP '.$res->status()),
        ];
    }

    /**
     * @return array{ok: bool, data: mixed, error: ?string}
     */
    public function checkCredentials(WhatsappCredential $c): array {
        $res = Http::withToken($c->access_token)->timeout(15)
            ->get("{$this->base($c)}/{$c->phone_number_id}", ['fields' => 'display_phone_number,verified_name']);

        return ['ok' => $res->successful(), 'data' => $res->json(), 'error' => $res->json('error.message')];
    }
}
