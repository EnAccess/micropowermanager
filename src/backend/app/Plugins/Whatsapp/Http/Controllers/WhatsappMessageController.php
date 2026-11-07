<?php

namespace App\Plugins\Whatsapp\Http\Controllers;

use App\Models\Sms;
use App\Plugins\Whatsapp\Models\WhatsappMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class WhatsappMessageController extends Controller {
    public function __construct(
        private WhatsappMessage $message,
        private Sms $sms,
    ) {}

    /**
     * Message log: each WhatsApp attempt joined with MPM's own SMS record.
     */
    public function index(): JsonResponse {
        $smsTable = $this->sms->getTable();

        $messages = $this->message->newQuery()
            ->leftJoin($smsTable, "{$smsTable}.id", '=', 'whatsapp_messages.sms_id')
            ->select(
                'whatsapp_messages.*',
                "{$smsTable}.receiver",
                "{$smsTable}.body",
                "{$smsTable}.status as sms_status",
                "{$smsTable}.error_message"
            )
            ->orderByDesc('whatsapp_messages.id')
            ->paginate(20);

        return response()->json($messages);
    }
}
