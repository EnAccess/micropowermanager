<?php

namespace App\Plugins\MicroStarMeter\Services;

use App\Plugins\MicroStarMeter\Models\MicroStarCredential;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MicroStarCertificateService {
    public function upload(Request $request, MicroStarCredential $credentials): MicroStarCredential {
        $file = $request->file('cert');
        $companyId = app(UserService::class)->getCompanyId();

        $certificatePath = "certs/companies/{$companyId}";

        Storage::putFileAs($certificatePath, $file, $file->getClientOriginalName());

        $credentials->update([
            'certificate_path' => $certificatePath,
            'certificate_file_name' => $file->getClientOriginalName(),
        ]);

        return $credentials;
    }

    public function getUploadedCertificate(MicroStarCredential $credentials): string {
        if (!$credentials->certificate_path || !$credentials->certificate_file_name) {
            return '';
        }

        $filePath = trim($credentials->certificate_path, '/').'/'.$credentials->certificate_file_name;

        if (Storage::exists($filePath)) {
            return $credentials->certificate_file_name;
        } else {
            $credentials->update(['certificate_path' => null, 'certificate_file_name' => null]);

            return '';
        }
    }
}
