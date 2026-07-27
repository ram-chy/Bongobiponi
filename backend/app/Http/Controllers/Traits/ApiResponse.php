<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse(mixed $data = null, string $message = '', int $code = 200): JsonResponse
    {
        $response = ['success' => true];

        if ($message !== '') {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    protected function errorResponse(string $message, int $code = 400, ?array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function deleteErrorResponse(array $resultData, int $code = 422): JsonResponse
    {
        return response()->json($resultData, $code);
    }
}
