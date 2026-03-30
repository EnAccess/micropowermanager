<?php

namespace App\Services\Interfaces;

use Illuminate\Http\Request;

interface IApiResolver {
    public function resolveCompanyId(Request $request): int;
}
