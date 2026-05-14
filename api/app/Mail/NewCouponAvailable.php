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
 * "New coupon available" broadcast mail.
 *
 * Dispatched once per recipient (NOT BCC'd) so each user gets a real
 * To: header — required for unsubscribe-link / list-unsubscribe headers
 * later, and avoids leaking the customer list to other recipients.
 *
 * Cheap to construct (just 2 model refs); the queue worker will fan out.
 */
class NewCouponAvailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Coupons $coupon;
    public User $user;

    public function __construct(Coupons $coupon, User $user)
    {
        $this->coupon = $coupon;
        $this->user   = $user;
    }

    public function envelope(): Envelope
    {
        $code = $this->coupon->coupon_code;

        return new Envelope(
            subject: "Új kupon: {$code} - Buttercup Perfumery",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.new-coupon',
            with: [
                'name'       => $this->user->name,
                'coupon'     => $this->coupon,
                // Pre-format the discount line so the Blade template stays
                // dumb. Percentage shows as "20%", fixed as "1 500 Ft".
                'discountLabel' => $this->coupon->discount_type === 'percentage'
                    ? rtrim(rtrim(number_format($this->coupon->discount_value, 2, '.', ''), '0'), '.') . '%'
                    : number_format($this->coupon->discount_value, 0, ',', ' ') . ' Ft',
                'expiryLabel'   => optional($this->coupon->expiry_date)->format('Y. m. d.'),
                'shopUrl'       => rtrim(
                    config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')),
                    '/'
                ) . '/shop',
            ],
        );
    }
}
