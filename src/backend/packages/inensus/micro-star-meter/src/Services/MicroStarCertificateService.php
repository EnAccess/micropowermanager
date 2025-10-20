<?php

namespace Inensus\MicroStarMeter\Services;

use App\Services\UserService;
use App\Support\AppStorage;
use Illuminate\Http\Request;
use Inensus\MicroStarMeter\Models\MicroStarCredential;

class MicroStarCertificateService {
    public function upload(Request $request, MicroStarCredential $credentials): MicroStarCredential {
        $file = $request->file('cert');
        $companyId = app(UserService::class)->getCompanyId();

        $certificatePath = "certs/companies/{$companyId}";

        AppStorage::putFileAs($certificatePath, $file, $file->getClientOriginalName());

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

        if (AppStorage::exists($filePath)) {
            return $credentials->certificate_file_name;
        } else {
            $credentials->update(['certificate_path' => null, 'certificate_file_name' => null]);

            return '';
        }
    }
}
