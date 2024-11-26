<?php

namespace Inensus\MicroStarMeter\Services;

use App\Services\UserService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MicroStarCertificateService {
    public function upload($request, $credentials) {
        $file = $request->file('cert');
        $companyId = app()->make(UserService::class)->getCompanyId();

        if (!File::isDirectory(storage_path("/app/certs/companies/$companyId"))) {
            File::makeDirectory(storage_path("/app/certs/companies/$companyId"), 0777, true, true);
        }

        $certificatePath = "/certs/companies/$companyId/";
        $fileName = $file->getClientOriginalName();
        Storage::disk('local')->putFileAs($certificatePath, $file, $fileName);
        $credentials->update([
            'certificate_path' => $certificatePath,
            'certificate_file_name' => $fileName,
        ]);

        return $credentials;
    }

    public function getUploadedCertificate($credentials) {
        if (!$credentials->certificate_path || !$credentials->certificate_file_name) {
            return '';
        }

        $filePath = storage_path('app'.$credentials->certificate_path.'/'.$credentials->certificate_file_name);
        if (File::exists($filePath)) {
            return $credentials->certificate_file_name;
        } else {
            $credentials->update(['certificate_path' => null, 'certificate_file_name' => null]);

            return '';
        }
    }
}
