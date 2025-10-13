<?php

namespace Inensus\BulkRegistration\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Spatie\Geocoder\Exceptions\CouldNotGeocode;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler {
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [];

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
     * @throws \Exception
     */
    public function report(\Throwable $exception): void {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Throwable
     */
    public function render($request, \Throwable $exception) {
        if ($exception instanceof CouldNotGeocode) {
            new GoogleMapsApiException(json_encode($exception->getMessage()));
        }

        return parent::render($request, $exception);
    }
}
