<?php

namespace MPM\Sharding\ApiResolvers;

use Illuminate\Http\Request;

interface ApiResolverInterface {
    public function resolveCompanyId(Request $request): int;
}
