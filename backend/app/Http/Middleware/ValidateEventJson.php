<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

class ValidateEventJson
{
    private const MAX_REQUEST_BYTES = 131072;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isJson()) {
            return $this->error($request, 'VALIDATION_FAILED', 'The request contains invalid data.', [
                'content_type' => ['The Content-Type header must be application/json.'],
            ], 422);
        }

        $content = $request->getContent();

        if (strlen($content) > self::MAX_REQUEST_BYTES) {
            return $this->error($request, 'PAYLOAD_TOO_LARGE', 'The request body exceeds 128 KiB.', [], 413);
        }

        try {
            $payload = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->error($request, 'INVALID_JSON', 'The request body contains invalid JSON.', [], 400);
        }

        if (! $payload instanceof stdClass) {
            return $this->error($request, 'VALIDATION_FAILED', 'The request contains invalid data.', [
                'body' => ['The request body must be a JSON object.'],
            ], 422);
        }

        if (property_exists($payload, 'context') && $payload->context !== null && ! $payload->context instanceof stdClass) {
            return $this->error($request, 'VALIDATION_FAILED', 'The request contains invalid data.', [
                'context' => ['The context field must be a JSON object.'],
            ], 422);
        }

        return $next($request);
    }

    private function error(Request $request, string $code, string $message, array $details, int $status): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => empty($details) ? (object) [] : $details,
                'request_id' => $request->attributes->get('request_id'),
            ],
        ], $status);
    }
}
