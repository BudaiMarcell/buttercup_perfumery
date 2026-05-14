<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Attach a unique request ID to every HTTP request.
 *
 * Why bother:
 *   - Customers occasionally see an error and screenshot it. With a
 *     request ID in the response, support can grep the logs and find
 *     the exact entry without guesswork.
 *   - Sentry, Laravel logs, and the queue worker all pick up the ID
 *     from the context so a single ID stitches together the API call,
 *     the queued mail, and any background job that fired.
 *
 * Honors an inbound X-Request-Id header so a frontend can correlate
 * across calls; falls back to a fresh UUID otherwise. The ID is added
 * to:
 *   - The Laravel log context for the duration of the request
 *   - The outbound response header (so the browser can echo it back)
 */
class RequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->header('X-Request-Id');
        $requestId = $this->sanitize($incoming) ?? (string) Str::uuid();

        $request->headers->set('X-Request-Id', $requestId);

        // Bind into Laravel's logger so every log() call during this
        // request automatically tags itself with the ID. The closure
        // wins the moment we exit the request lifecycle.
        Log::withContext(['request_id' => $requestId]);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    /**
     * Defensively trim inbound IDs — never trust the client to give us
     * something sane to log. Cap length, allow only safe characters.
     */
    private function sanitize(?string $value): ?string
    {
        if (!$value) return null;
        $value = trim($value);
        if ($value === '')                                   return null;
        if (strlen($value) > 64)                             return null;
        if (!preg_match('/^[A-Za-z0-9._\\-]+$/', $value))    return null;
        return $value;
    }
}
