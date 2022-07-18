<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use File;

class LanguageController extends Controller
{
    public function index(): ApiResource
    {
        $path = resource_path('assets/locales');
        $files = collect(\File::allFiles($path));
        $filteredFiles = $files->map(
            function ($file) {
                if ($file->getExtension() === 'json') {
                    return $file->getFilename();
                }
            }
        );
        return ApiResource::make($filteredFiles);
    }
}
