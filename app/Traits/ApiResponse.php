<?php

namespace App\Traits;

trait ApiResponse
{
    public static function apiResponse($data, $message = null, $status = 200)
    {
        $response = [
            'data'   => !empty($data) ? $data : [],
            'status' => in_array($status, [200, 201, 202, 203]),
            'code'   => $status,
        ];

        if ($message !== null) {
            $response = ['message' => $message] + $response;
        }

        return response()->json($response, $status);
    }

    public static function apiResponseStored($data, $message = null)
    {
        return self::apiResponse($data, $message, 201);
    }

    public static function apiResponseShow($data, $message = null)
    {
        return self::apiResponse($data, $message, 200);
    }

    public static function apiResponseUpdated($data, $message = null)
    {
        return self::apiResponse($data, $message, 200);
    }

    public static function apiResponseDeleted($message = null)
    {
        return self::apiResponse([], $message, 200);
    }
}