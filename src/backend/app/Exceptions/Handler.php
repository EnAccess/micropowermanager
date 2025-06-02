<?php

namespace App\Exceptions;

use App\Traits\RestExceptionHandler;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler {
    use RestExceptionHandler;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param \Throwable $exception
     *
     * @return void
     */
    public function report(\Throwable $exception) {
        Log::critical(get_class($exception), ['message' => $exception->getMessage(), 'trace' => array_slice($exception->getTrace(), 0, 10)]);
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request    $request
     * @param \Throwable $exception
     *
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function render($request, \Throwable $exception) {
        // api request outputs are json
        if ($request->expectsJson()
            || strpos($request->url(), '/api') !== false) {
            $exceptionForJson = $exception instanceof \Exception
                ? $exception
                : new \Exception(
                    $exception->getMessage(),
                    (int) $exception->getCode(),
                    $exception
                );

            return $this->getJsonResponseForException($request, $exceptionForJson);
        }

        return parent::render($request, $exception);
    }
}
