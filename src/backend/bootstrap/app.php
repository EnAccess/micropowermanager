<?php

use App\Console\Commands\MailApplianceDebtsCommand;
use App\Http\Middleware\AdminJWT;
use App\Http\Middleware\AgentBalanceMiddleware;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Middleware\Transaction;
use App\Http\Middleware\TransactionRequest;
use App\Http\Middleware\UserDefaultDatabaseConnectionMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Psr\Log\LogLevel;
use Tymon\JWTAuth\Exceptions\JWTException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(UserDefaultDatabaseConnectionMiddleware::class);

        $middleware->alias([
            'transaction.auth' => Transaction::class,
            'transaction.request' => TransactionRequest::class,
            'admin' => AdminJWT::class,
            'jwt.verify' => JwtMiddleware::class,
            'agent.balance' => AgentBalanceMiddleware::class,
        ]);

        // additional middleware group to `web` and `api` default groups
        $middleware->group('agent_api', [
            Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // JWTExceptions happen quite frequently.
        // User token might expire, web scraper trying to access unauthrized areas, etc...
        // Lowering the LogLevel here to not spam our logging.
        $exceptions->level(JWTException::class, LogLevel::INFO);

        $exceptions->render(function (JWTException $e) {
            return response()->json(['error' => 'Unauthorized. '.$e->getMessage().' Make sure you are logged in.'], 401);
        });

        $exceptions->render(function (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Model not found '.implode(' ', $e->getIds()),
                'status_code' => 404,
            ]);
        });
        $exceptions->render(function (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'status_code' => 422,
            ], 422);
        });
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('reports:city-revenue weekly')->weeklyOn(1, '3:00');
        $schedule->command('reports:city-revenue monthly')->monthlyOn(1, '3:00');
        $schedule->command('reports:outsource')->monthlyOn(1, '3:30');
        $schedule->command('sms:resend-rejected 5')->everyMinute();
        $schedule->command('update:cachedClustersDashboardData')->everyFifteenMinutes();
        $schedule->command('asset-rate:check')->dailyAt('00:00');
        // will run on the last day of the month
        $schedule->command(MailApplianceDebtsCommand::class)->weeklyOn(1, '6:00');
        $schedule->command('prospect:sync')->dailyAt('00:00');
    })
    ->create();
