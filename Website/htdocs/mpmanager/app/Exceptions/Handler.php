<?php

namespace App\Exceptions;

use App\Traits\RestExceptionHandler;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    use RestExceptionHandler;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request   $request
     * @param  Throwable $exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request, Exception|Throwable $exception)
    {
        // api request outputs are json
        if (preg_match('/.*\.local\/api.*/', $request->url()) || preg_match('/.*\.com\/api.*/', $request->url())) {
            return $this->getJsonResponseForException($request, $exception);
        }

        return parent::render($request, $exception);
    }
}
