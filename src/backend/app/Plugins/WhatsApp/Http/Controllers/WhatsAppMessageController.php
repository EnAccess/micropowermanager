<?php

namespace App\Plugins\WhatsApp\Http\Controllers;

use App\Plugins\WhatsApp\Http\Resources\WhatsAppMessageResource;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

#[Group('Plugins / WhatsApp', 'API endpoints for WhatsApp Business API integration')]
class WhatsAppMessageController extends Controller {
    /**
     * List recent WhatsApp messages, optionally filtered by status.
     */
    public function index(Request $request): AnonymousResourceCollection {
        $query = new WhatsAppMessage()->newQuery()->latest('id');

        $status = strtolower($request->string('status')->toString());
        if ($status !== '') {
            $query->where('status', $status);
        }

        return WhatsAppMessageResource::collection($query->limit(200)->get());
    }
}
