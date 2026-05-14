<?php

namespace App\Mail;

use App\Models\Coupons;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * One-shot welcome mail dispatched right after successful registration.
 *
 * Goes out alongside (not instead of) the VerifyEmail — verify is the
 * security flow, this is the marketing flow. The two are deliberately
 * separate so we can change them independently and so a bounced or
 * delayed welcome doesn't block the verification link.
 *
 * If an active, non-expired coupon is in the DB we attach it as the
 * "current offer". When none exists the template renders without an
 * offer block; the welcome still works.
 */
class WelcomeOffers extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public ?Coupons $featuredCoupon;

    public function __construct(User $user, ?Coupons $featuredCoupon = null)
    {
        $this->user           = $user;
        $this->featuredCoupon = $featuredCoupon;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Üdv a Buttercup Perfumery csapatában! ✦',
        );
    }

    public function content(): Content
    {
        $shopUrl = rtrim(
            config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')),
            '/'
        ) . '/shop';

        // Pre-format the discount line so the Blade stays presentation-only.
        $discountLabel = null;
        $expiryLabel   = null;
        if ($this->featuredCoupon) {
            $discountLabel = $this->featuredCoupon->discount_type === 'percentage'
                ? rtrim(rtrim(number_format($this->featuredCoupon->discount_value, 2, '.', ''), '0'), '.') . '%'
                : number_format($this->featuredCoupon->discount_value, 0, ',', ' ') . ' Ft';

            $expiryLabel = optional($this->featuredCoupon->expiry_date)->format('Y. m. d.');
        }

        return new Content(
            markdown: 'emails.welcome',
            with: [
                'name'          => $this->user->name,
                'coupon'        => $this->featuredCoupon,
                'discountLabel' => $discountLabel,
                'expiryLabel'   => $expiryLabel,
                'shopUrl'       => $shopUrl,
            ],
        );
    }
}
