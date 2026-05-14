<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackingApiKeyMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-Tracking-Key');

        if ($apiKey !== config('analytics.tracking_key')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
