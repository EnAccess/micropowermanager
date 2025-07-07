<?php

namespace App\Services;

use App\Models\MailSettings;
use Illuminate\Http\Request;

class MailSettingsService {
    private MailSettings $mailSettings;

    public function __construct(MailSettings $mailSettings) {
        $this->mailSettings = $mailSettings;
    }

    public function list(): ?MailSettings {
        return $this->mailSettings->newQuery()->first();
    }

    public function create(Request $request): MailSettings {
        $mailSettings = $this->mailSettings->newQuery()->create([
            'mail_host' => $request->get('mail_host'),
            'mail_port' => $request->get('mail_port'),
            'mail_encryption' => $request->get('mail_encryption'),
            'mail_username' => $request->get('mail_username'),
            'mail_password' => $request->get('mail_password'),
        ]);

        return $mailSettings;
    }

    public function update(Request $request, MailSettings $mailSettings): ?MailSettings {
        $mailSettings->update([
            'mail_host' => $request->get('mail_host'),
            'mail_port' => $request->get('mail_port'),
            'mail_encryption' => $request->get('mail_encryption'),
            'mail_username' => $request->get('mail_username'),
            'mail_password' => $request->get('mail_password'),
        ]);

        $mailSettings->fresh();

        return $mailSettings->first();
    }
}
