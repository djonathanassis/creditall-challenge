<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    protected function successResponse(
        mixed $data = null,
        ?string $messageKey = null,
        array $parameters = [],
        int $status = 200
    ): JsonResponse {
        $response = ['success' => true];

        if ($messageKey !== null) {
            $response['message'] = __($messageKey, $parameters);
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    protected function successWithDataResponse(
        mixed $data,
        string $messageKey,
        array $parameters = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => __($messageKey, $parameters),
            'data' => $data,
        ], 200);
    }

    protected function createdResponse(
        mixed $data,
        string $messageKey,
        array $parameters = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => __($messageKey, $parameters),
            'data' => $data,
        ], 201);
    }

    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        ?string $messageKey = null,
        array $parameters = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];

        if ($messageKey !== null) {
            $response['message'] = __($messageKey, $parameters);
        }

        return response()->json($response, 200);
    }

    protected function errorResponse(
        string $messageKey,
        array $parameters = [],
        int $status = 422
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => __($messageKey, $parameters),
        ], $status);
    }

    protected function validationErrorResponse(
        mixed $errors,
        string $messageKey = 'api.errors.validation_failed',
        array $parameters = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => __($messageKey, $parameters),
            'errors' => $errors,
        ], 422);
    }

    protected function notFoundResponse(
        string $messageKey = 'api.errors.resource_not_found',
        array $parameters = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => __($messageKey, $parameters),
        ], 404);
    }

    protected function unauthorizedResponse(
        string $messageKey = 'api.errors.unauthorized',
        array $parameters = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => __($messageKey, $parameters),
        ], 401);
    }

    protected function forbiddenResponse(
        string $messageKey = 'api.errors.forbidden',
        array $parameters = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => __($messageKey, $parameters),
        ], 403);
    }

    protected function serverErrorResponse(
        string $messageKey = 'api.errors.server_error',
        array $parameters = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => __($messageKey, $parameters),
        ], 500);
    }

    protected function resourceResponse(
        JsonResource $resource,
        ?string $messageKey = null,
        array $parameters = [],
        int $status = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'data' => $resource,
        ];

        if ($messageKey !== null) {
            $response['message'] = __($messageKey, $parameters);
        }

        return response()->json($response, $status);
    }

    protected function collectionResponse(
        ResourceCollection $collection,
        ?string $messageKey = null,
        array $parameters = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'data' => $collection,
        ];

        if ($messageKey !== null) {
            $response['message'] = __($messageKey, $parameters);
        }

        return response()->json($response, 200);
    }
}
