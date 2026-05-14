<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\PasswordChangedNotice;
use App\Mail\VerifyEmail;
use App\Mail\WelcomeOffers;
use App\Models\Admin;
use App\Models\Coupons;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        try {
            Mail::to($user->email)->queue(new VerifyEmail($user));
        } catch (\Throwable $e) {
            \Log::warning('Failed to queue verification email', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        // Welcome email — separate from the verify mail so a bounced or
        // delayed welcome can never block the security flow. We attach
        // the freshest active, non-expired, not-fully-used coupon as a
        // "current offer"; if nothing qualifies, the template renders
        // without an offer block (still useful as a welcome).
        try {
            $featuredCoupon = Coupons::query()
                ->where('is_active', true)
                ->whereDate('expiry_date', '>=', now()->toDateString())
                ->where(function ($q) {
                    $q->whereNull('usage_limit')
                      ->orWhereColumn('used_count', '<', 'usage_limit');
                })
                ->orderByDesc('created_at')
                ->first();

            Mail::to($user->email)->queue(new WelcomeOffers($user, $featuredCoupon));
        } catch (\Throwable $e) {
            \Log::warning('Failed to queue welcome email', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token
        ], 201);
    }

    /**
     * Lockout policy: after this many failed attempts within
     * LOCKOUT_WINDOW seconds, the account is locked for the same
     * duration. Tuned so a human typing wrong twice on mobile won't
     * trip it, but a credential-stuffer hammering 1000 password
     * variants gets shut out fast.
     */
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_SECONDS = 900; // 15 minutes

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Per-EMAIL counter — separate from the route's per-IP throttle
        // (5/min in routes/api.php). Per-IP alone is gameable from
        // shared NATs (offices, mobile networks), and per-email alone
        // is gameable by attackers cycling through accounts. Both
        // layered together is the standard pattern.
        $emailKey = 'login.attempts.' . sha1(strtolower($request->input('email')));
        $attempts = (int) \Illuminate\Support\Facades\Cache::get($emailKey, 0);

        if ($attempts >= self::LOGIN_MAX_ATTEMPTS) {
            $retryIn = (int) \Illuminate\Support\Facades\Cache::get(
                $emailKey . '.cooldown_until',
                now()->addSeconds(self::LOGIN_LOCKOUT_SECONDS)->timestamp
            ) - now()->timestamp;
            $retryIn = max($retryIn, 60);

            \Log::warning('Login locked out', [
                'email'      => $request->input('email'),
                'ip'         => $request->ip(),
                'retry_in_s' => $retryIn,
            ]);

            return response()->json([
                'message'    => 'Túl sok sikertelen próbálkozás. Kérlek várj '
                              . (int) ceil($retryIn / 60)
                              . ' percet, vagy állítsd vissza a jelszavadat.',
                'locked'     => true,
                'retry_in_s' => $retryIn,
            ], 429);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            // Bump the counter. Cache::increment is atomic, so two
            // concurrent failed logins can't both read 4 and write 5.
            \Illuminate\Support\Facades\Cache::add($emailKey, 0, self::LOGIN_LOCKOUT_SECONDS);
            $now = (int) \Illuminate\Support\Facades\Cache::increment($emailKey);

            if ($now >= self::LOGIN_MAX_ATTEMPTS) {
                // Lock for the full window, regardless of remaining
                // TTL on the attempt counter.
                \Illuminate\Support\Facades\Cache::put(
                    $emailKey . '.cooldown_until',
                    now()->addSeconds(self::LOGIN_LOCKOUT_SECONDS)->timestamp,
                    self::LOGIN_LOCKOUT_SECONDS
                );
            }

            return response()->json([
                'message' => 'Hibás email vagy jelszó.'
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Successful login → reset the failed-attempt counter so a
        // legitimate user who fat-fingered a couple of times doesn't
        // get locked out on their next session.
        \Illuminate\Support\Facades\Cache::forget($emailKey);
        \Illuminate\Support\Facades\Cache::forget($emailKey . '.cooldown_until');

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function adminRegister(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users|unique:admins',
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
            'role'     => 'sometimes|string|max:50',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Admin::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role ?? 'admin',
        ]);

        $token = $user->createToken('admin_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sikeresen kijelentkezve.'
        ]);
    }

    /**
     * GDPR-friendly self-service account deletion.
     *
     * Confirms the password (so a stolen token alone can't burn the
     * account), then scrubs identifying fields rather than hard-
     * deleting the row. We keep the row + foreign keys intact so that:
     *
     *   - Past orders still belong to "someone" for accounting
     *   - The unique email slot is freed (gets nulled and the unique
     *     index allows multiple nulls in MySQL/InnoDB)
     *   - Wishlist + addresses + payment methods + tokens all cascade
     *     OR are explicitly nuked here.
     *
     * The user is told this is irreversible. The frontend logs them
     * out immediately on success.
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Defence-in-depth: re-verify the password even though the
        // request is already authenticated. A bearer token leaked
        // through XSS shouldn't be enough to delete the account.
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Hibás jelszó. A fiók törléséhez igazold a jelszavadat.',
            ], 422);
        }

        $userId = $user->id;

        \DB::transaction(function () use ($user) {
            // Scrub PII columns. Email -> NULL so the unique slot frees
            // up; name and phone get neutral placeholders so admin
            // queries don't crash on null name renders.
            $user->forceFill([
                'email'             => null,
                'name'              => '[deleted user]',
                'phone'             => null,
                'email_verified_at' => null,
                // Random un-hashable password so a hash collision
                // can't grant access.
                'password'          => Hash::make(Str::random(60)),
            ])->save();

            // Wipe ancillary PII the User row doesn't hold directly.
            // Orders are preserved (user_id stays for accounting); the
            // user's *name on file* lives in the User row we just
            // scrubbed, not on the order itself.
            $user->tokens()->delete();
            $user->addresses()->delete();
            $user->wishlist()->delete();
            // Payment methods are pure-PII metadata — never want them
            // hanging around once the account is gone.
            if (method_exists($user, 'paymentMethods')) {
                $user->paymentMethods()->delete();
            }
        });

        return response()->json([
            'message' => 'A fiókod adatait töröltük. Köszönjük, hogy nálunk voltál.',
            'user_id' => $userId,
        ]);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'unique:users,email,' . $user->id],
            'phone' => 'sometimes|nullable|string|max:32',
        ]);

        if (isset($validated['email']) && $validated['email'] !== $user->email) {
            $validated['email_verified_at'] = null;

            $user->update($validated);

            try {
                Mail::to($user->email)->queue(new VerifyEmail($user));
            } catch (\Throwable $e) {
                \Log::warning('Failed to queue verification email after email change', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        } else {
            $user->update($validated);
        }

        return new UserResource($user->fresh());
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Password updated.',
            'token'   => $token,
        ]);
    }

    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->email), $hash)) {
            return response()->json(['message' => 'Érvénytelen ellenőrző link.'], 403);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
        return redirect($frontendUrl . '/?verified=1');
    }

    /**
     * Trigger a password-reset email for the currently authenticated user.
     *
     * Unlike /forgot-password (public, anti-enumeration), this endpoint is
     * for an already-signed-in user who wants to change their password
     * without typing the current one — we look up the email from the
     * session, never trust client input, and report the result honestly.
     */
    public function requestPasswordResetLink(Request $request)
    {
        $user = $request->user();

        $status = PasswordBroker::sendResetLink(['email' => $user->email]);

        if ($status === PasswordBroker::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Elküldtük a jelszó-visszaállító linket a(z) '
                           . $user->email . ' címre.',
                'email'   => $user->email,
            ]);
        }

        // Broker self-throttle: too many recent requests for this email.
        if ($status === PasswordBroker::RESET_THROTTLED) {
            return response()->json([
                'message' => 'Túl gyakran kérted ezt — kérlek várj egy percet.',
            ], 429);
        }

        return response()->json([
            'message' => 'Nem sikerült elindítani a jelszó-visszaállítást. Próbáld újra később.',
        ], 500);
    }

    public function resendVerification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Az e-mail cím már megerősítve.'
            ], 200);
        }

        try {
            Mail::to($user->email)->queue(new VerifyEmail($user));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Nem sikerült elküldeni a megerősítő e-mailt. Próbáld újra később.'
            ], 503);
        }

        return response()->json([
            'message' => 'Megerősítő e-mail elküldve.'
        ]);
    }

    /**
     * Step 1 of password reset: user enters their email, we send them a
     * link with a one-time token. The actual mail is dispatched by
     * Laravel's password broker via User::sendPasswordResetNotification.
     *
     * Important: we always return 200 with the same message regardless of
     * whether the email exists. This prevents email enumeration (an
     * attacker probing which addresses are registered).
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Status is one of:
        //   PASSWORD_RESET_LINK_SENT   — user found, mail queued
        //   INVALID_USER               — no such email
        //   RESET_THROTTLED            — too soon since the last request
        // We swallow all of them and return the same generic message so a
        // probe can't distinguish "exists" from "does not exist".
        PasswordBroker::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Ha létezik fiók ezzel az e-mail címmel, '
                       . 'elküldtük a jelszó-visszaállító linket.',
        ]);
    }

    /**
     * Step 2 of password reset: user submits the token from the email
     * along with a new password. Broker validates the token (signature +
     * 60-min expiry from config/auth.php), then we hash and save the
     * password and revoke every existing Sanctum token so old phones /
     * tabs can't keep using the account.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => [
                'required',
                'confirmed',
                Password::min(10)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $changedUser = null;
        $status = PasswordBroker::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use (&$changedUser) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Kill every active Sanctum token so a stolen device or an
                // old browser session can't keep the account warm after
                // the password rotation.
                $user->tokens()->delete();

                // Hand the user out of the closure so we can fire the
                // confirmation mail AFTER the broker confirms success.
                $changedUser = $user;
            }
        );

        if ($status === PasswordBroker::PASSWORD_RESET) {
            // Security-confirmation mail. Failure is non-fatal — the
            // password is already changed, so we log and move on.
            if ($changedUser) {
                try {
                    Mail::to($changedUser->email)
                        ->queue(new PasswordChangedNotice($changedUser));
                } catch (\Throwable $e) {
                    \Log::warning('Failed to queue password-changed notice', [
                        'user_id' => $changedUser->id,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'message' => 'Jelszó sikeresen frissítve. Most már bejelentkezhetsz.',
            ]);
        }

        // Map the broker's translation keys to a friendlier message; the
        // most common case is an expired or already-used token.
        $message = match ($status) {
            PasswordBroker::INVALID_TOKEN  => 'A megadott link érvénytelen vagy lejárt. Kérj újat.',
            PasswordBroker::INVALID_USER   => 'Nem található felhasználó ezzel az e-mail címmel.',
            default                        => 'Nem sikerült visszaállítani a jelszót. Kérj új linket.',
        };

        return response()->json(['message' => $message], 422);
    }
}
