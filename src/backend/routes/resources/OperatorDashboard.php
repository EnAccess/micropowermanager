<?php

use App\Http\Controllers\OperatorDashboardController;
use Illuminate\Support\Facades\Route;

// The operator dashboard spans every tenant, so it is guarded by operator Basic
// credentials instead of a tenant JWT. The path prefix is also excluded in
// UserDefaultDatabaseConnectionMiddleware, which would otherwise try to resolve a
// tenant from a token that does not exist here.
Route::group(['prefix' => 'operator/dashboard', 'middleware' => 'basic.auth:operator'], static function () {
    Route::get('/', [OperatorDashboardController::class, 'index']);
    Route::post('/refresh', [OperatorDashboardController::class, 'refresh']);
    Route::get('/tenants/{companyId}', [OperatorDashboardController::class, 'show'])->whereNumber('companyId');
    Route::post('/tenants/{companyId}/refresh', [OperatorDashboardController::class, 'refresh'])
        ->whereNumber('companyId');
});
