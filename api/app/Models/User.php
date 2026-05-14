<?php

namespace App\Models;

use App\Mail\ResetPasswordEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Override Laravel's default password-reset notification so the link
     * points at the Vue SPA's /reset-password page (the user lands on the
     * frontend, not on a Laravel route) and the email matches the brand
     * via our custom Mailable.
     */
    public function sendPasswordResetNotification($token): void
    {
        $frontend = rtrim(
            config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')),
            '/'
        );

        $resetUrl = $frontend
            . '/reset-password'
            . '?token=' . $token
            . '&email=' . urlencode($this->getEmailForPasswordReset());

        Mail::to($this->getEmailForPasswordReset())
            ->queue(new ResetPasswordEmail($this, $resetUrl));
    }
}
