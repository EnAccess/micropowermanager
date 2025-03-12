<?php

namespace MPM\TenantResolver\ApiResolvers;

use Illuminate\Http\Request;

interface ApiResolverInterface {
    public function resolveCompanyId(Request $request): int;
}
