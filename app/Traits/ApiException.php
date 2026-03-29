<?php

namespace App\Traits;

use Illuminate\Database\QueryException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

trait ApiException
{
    use ApiResponse;

    public static function apiException($e)
    {
        /** Validation (e.g. request data) */
        if ($e instanceof ValidationException) {
            return ApiResponse::apiResponse($e->errors(), __('http-statuses.422'), 422);
        }

        /** Business logic (e.g. invalid order state) */
        if ($e instanceof UnprocessableEntityHttpException) {
            return ApiResponse::apiResponse(null, $e->getMessage(), 422);
        }

        /** Bad request */
        if ($e instanceof InvalidArgumentException) {
            return ApiResponse::apiResponse(null, $e->getMessage(), 400);
        }

        /** Not found */
        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return ApiResponse::apiResponse(null, __('http-statuses.404'), 404);
        }

        /** Unauthenticated */
        if ($e instanceof AuthenticationException) {
            return ApiResponse::apiResponse(null, __('http-statuses.401'), 401);
        }

        /** Unauthorized / forbidden */
        if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
            return ApiResponse::apiResponse(null, __('http-statuses.403'), 403);
        }

        /** Database errors */
        if ($e instanceof QueryException) {
            return ApiResponse::apiResponse(null, config('app.debug') ? $e->getMessage() : __('http-statuses.500'), 500);
        }

        /** Predis / connection issues */
        if ($e instanceof \Predis\Connection\ConnectionException) {
            return ApiResponse::apiResponse(null, __('http-statuses.503'), 503);
        }

        /** Mail transport issues */
        if (
            $e instanceof \Symfony\Component\Mailer\Exception\TransportException ||
            $e instanceof \Symfony\Component\Mailer\Exception\TransportExceptionInterface
        ) {
            return ApiResponse::apiResponse(null, __('http-statuses.503'), 503);
        }

        /** Generic HTTP exceptions */
        if ($e instanceof HttpException) {
            return ApiResponse::apiResponse(null, $e->getMessage(), $e->getStatusCode());
        }

        /** Fallback */
        return ApiResponse::apiResponse(null, config('app.debug') ? $e->getMessage() : __('http-statuses.500'), 500);
    }
}