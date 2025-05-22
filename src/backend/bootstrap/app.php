<?php

use App\Http\Middleware\AdminJWT;
use App\Http\Middleware\AgentBalanceMiddleware;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Middleware\Transaction;
use App\Http\Middleware\TransactionRequest;
use App\Http\Middleware\UserDefaultDatabaseConnectionMiddleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withSingletons([
        Illuminate\Contracts\Console\Kernel::class => App\Console\Kernel::class,
        Illuminate\Contracts\Debug\ExceptionHandler::class =>
    App\Exceptions\Handler::class
    ])
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->use([
            UserDefaultDatabaseConnectionMiddleware::class,
        ]);
        $middleware->appendToGroup('agent_api', [
            'bindings',
        ]);
        $middleware->alias([
            'bindings' => SubstituteBindings::class,
            'transaction.auth' => Transaction::class,
            'transaction.request' => TransactionRequest::class,
            'admin' => AdminJWT::class,
            'jwt.verify' => JwtMiddleware::class,
            'agent.balance' => AgentBalanceMiddleware::class,
        ]);
    })
    ->create();
