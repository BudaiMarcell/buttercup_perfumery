<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Pre-flight audit you run on a freshly deployed server to catch the
 * classic "I forgot to flip APP_DEBUG" / "APP_KEY is empty" / "queue
 * is using sync driver in prod" gotchas BEFORE the first user hits
 * the site.
 *
 * Usage: php artisan check:production
 *
 * Exit code is 0 when every check passes, 1 if any FAIL/WARN that
 * matters. Use in CI / deploy scripts:
 *
 *     php artisan check:production || exit 1
 */
class CheckProduction extends Command
{
    protected $signature = 'check:production';
    protected $description = 'Audit env, runtime and infrastructure for production-readiness';

    /** Tally of failures so the exit code reflects the result. */
    private int $failures = 0;
    private int $warnings = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->info('━━━ Production-readiness audit ━━━');
        $this->newLine();

        $this->checkEnv();
        $this->newLine();
        $this->checkApp();
        $this->newLine();
        $this->checkInfra();
        $this->newLine();
        $this->checkMail();
        $this->newLine();
        $this->checkSecrets();

        $this->newLine();
        $this->line(str_repeat('─', 60));
        if ($this->failures === 0 && $this->warnings === 0) {
            $this->info('✓ All checks passed. You are clear to ship.');
            return self::SUCCESS;
        }

        if ($this->failures > 0) {
            $this->error(
                "✗ {$this->failures} fail(s), {$this->warnings} warn(s). DO NOT ship until the failures are resolved."
            );
            return self::FAILURE;
        }

        $this->warn("⚠ 0 fails, {$this->warnings} warn(s). Review the warnings before shipping.");
        return self::FAILURE;
    }

    // ── Checks ─────────────────────────────────────────────────────

    private function checkEnv(): void
    {
        $this->section('Environment');

        $env = config('app.env');
        if ($env === 'production') {
            $this->pass("APP_ENV = production");
        } else {
            $this->warn_("APP_ENV = {$env} (expected 'production')");
        }

        if (config('app.debug') === true) {
            $this->fail("APP_DEBUG is TRUE — leaks stack traces to users");
        } else {
            $this->pass("APP_DEBUG = false");
        }
    }

    private function checkApp(): void
    {
        $this->section('App key + URL');

        $key = config('app.key');
        if (empty($key)) {
            $this->fail("APP_KEY is empty — encrypted cookies and signed URLs will break");
        } elseif (str_starts_with($key, 'base64:') && strlen($key) >= 50) {
            $this->pass("APP_KEY looks well-formed");
        } else {
            $this->warn_("APP_KEY is set but format looks unusual: {$key}");
        }

        $url = config('app.url');
        if (str_starts_with((string) $url, 'http://localhost')) {
            $this->warn_("APP_URL = {$url} (still pointing at localhost)");
        } else {
            $this->pass("APP_URL = {$url}");
        }
    }

    private function checkInfra(): void
    {
        $this->section('Infrastructure (database + redis + queue)');

        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $this->pass("MySQL is reachable");
        } catch (\Throwable $e) {
            $this->fail("MySQL: " . $e->getMessage());
        }

        try {
            $reply = Redis::connection()->ping();
            if ($reply === true || $reply === '+PONG' || $reply === 'PONG') {
                $this->pass("Redis is reachable");
            } else {
                $this->warn_("Redis PING returned unexpected reply: " . var_export($reply, true));
            }
        } catch (\Throwable $e) {
            $this->fail("Redis: " . $e->getMessage());
        }

        $queue = config('queue.default');
        if ($queue === 'sync') {
            $this->fail("QUEUE_CONNECTION = sync — mails block the request thread in prod");
        } else {
            $this->pass("QUEUE_CONNECTION = {$queue}");
        }

        $cache = config('cache.default');
        if ($cache === 'array') {
            $this->fail("CACHE_STORE = array — every request starts with an empty cache");
        } else {
            $this->pass("CACHE_STORE = {$cache}");
        }
    }

    private function checkMail(): void
    {
        $this->section('Mail');

        $mailer = config('mail.default');
        if ($mailer === 'log') {
            $this->warn_("MAIL_MAILER = log — outgoing mail goes to storage/logs/laravel.log, never to recipients");
        } elseif ($mailer === 'array') {
            $this->fail("MAIL_MAILER = array — mail is discarded entirely (test mode)");
        } else {
            $this->pass("MAIL_MAILER = {$mailer}");
        }

        $from = config('mail.from.address');
        if (empty($from) || str_ends_with((string) $from, '.local')) {
            $this->warn_("MAIL_FROM_ADDRESS = {$from} (looks like a placeholder)");
        } else {
            $this->pass("MAIL_FROM_ADDRESS = {$from}");
        }
    }

    private function checkSecrets(): void
    {
        $this->section('Secrets');

        $tracking = env('TRACKING_API_KEY');
        if (empty($tracking)) {
            $this->fail("TRACKING_API_KEY is empty — frontend analytics will be silently dropped");
        } else {
            $this->pass("TRACKING_API_KEY is set");
        }

        $dbPw = env('DB_PASSWORD');
        if (in_array($dbPw, ['', 'password', 'root', 'change-me-app-password', 'change-me-root-password'], true)) {
            $this->fail("DB_PASSWORD looks like a placeholder");
        } else {
            $this->pass("DB_PASSWORD is set");
        }
    }

    // ── Output helpers ─────────────────────────────────────────────

    private function section(string $title): void
    {
        $this->line("  <fg=cyan>{$title}</>");
    }

    private function pass(string $msg): void
    {
        $this->line("    <fg=green>✓</> {$msg}");
    }

    private function warn_(string $msg): void
    {
        $this->line("    <fg=yellow>⚠</> {$msg}");
        $this->warnings++;
    }

    private function fail(string $msg): void
    {
        $this->line("    <fg=red>✗</> {$msg}");
        $this->failures++;
    }
}
