<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Public health check endpoint for uptime monitors, load balancers and
 * the "did the container start clean?" check inside the Docker
 * HEALTHCHECK directive.
 *
 * Two endpoints:
 *   GET /api/health        — full check: 200 OK if every dep is reachable,
 *                            503 with per-check breakdown otherwise.
 *   GET /api/health/live   — liveness probe: always 200 as long as PHP-FPM
 *                            answers. Use this to gate "is the process up?"
 *                            without coupling to MySQL/Redis availability.
 *
 * Why two: a slow database shouldn't make Kubernetes/Traefik kill the pod
 * (which makes recovery worse, not better); only the readiness check
 * should react to that. Liveness only fires if the process is truly stuck.
 */
class HealthController extends Controller
{
    public function live()
    {
        return response()->json([
            'status' => 'ok',
            'time'   => now()->toIso8601String(),
        ]);
    }

    public function ready()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis'    => $this->checkRedis(),
        ];

        $allOk = collect($checks)->every(fn ($c) => $c['ok']);

        return response()->json([
            'status'  => $allOk ? 'ok' : 'degraded',
            'time'    => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'checks'  => $checks,
        ], $allOk ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        $start = microtime(true);
        try {
            DB::connection()->getPdo();
            // Cheap query that touches the actual DB, not just the pool.
            DB::select('SELECT 1');
            return [
                'ok'      => true,
                'latency' => $this->latencyMs($start),
            ];
        } catch (Throwable $e) {
            return [
                'ok'      => false,
                'latency' => $this->latencyMs($start),
                'error'   => $e->getMessage(),
            ];
        }
    }

    private function checkRedis(): array
    {
        $start = microtime(true);
        try {
            // PING is the canonical "are you there?" — avoids actually
            // writing to Redis (which would pollute the queue).
            $reply = Redis::connection()->ping();

            // phpredis returns "+PONG" or true depending on version;
            // accept either.
            $ok = $reply === true || $reply === '+PONG' || $reply === 'PONG';

            return [
                'ok'      => $ok,
                'latency' => $this->latencyMs($start),
            ];
        } catch (Throwable $e) {
            return [
                'ok'      => false,
                'latency' => $this->latencyMs($start),
                'error'   => $e->getMessage(),
            ];
        }
    }

    private function latencyMs(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }
}
