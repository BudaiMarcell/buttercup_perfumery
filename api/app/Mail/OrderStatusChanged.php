<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Your order's status changed" notification.
 *
 * Fired from two paths:
 *   1. AdminOrderController::updateStatus() — when staff moves the order
 *      through the pipeline (processing → shipped → arrived).
 *   2. OrderController::cancel() — when the customer cancels a pending
 *      order themselves.
 *
 * Either way, the user gets a consistent, branded mail. The blade
 * template picks the right copy based on the new status, so there is
 * no per-status mailable explosion.
 */
class OrderStatusChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $newStatus;
    public ?string $previousStatus;

    public function __construct(Order $order, string $newStatus, ?string $previousStatus = null)
    {
        $this->order          = $order->load(['items.product', 'address', 'user']);
        $this->newStatus      = $newStatus;
        $this->previousStatus = $previousStatus;
    }

    public function envelope(): Envelope
    {
        // Subject changes per status so the customer can scan their
        // inbox and know what to expect without opening the mail.
        $subject = match ($this->newStatus) {
            'processing' => "Rendelésed feldolgozás alatt - #{$this->order->id}",
            'shipped'    => "Rendelésed úton van - #{$this->order->id}",
            'arrived'    => "Rendelésed megérkezett - #{$this->order->id}",
            'canceled'   => "Rendelésed lemondva - #{$this->order->id}",
            'refunded'   => "Rendelésed visszatérítve - #{$this->order->id}",
            default      => "Rendelés frissítve - #{$this->order->id}",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        // Match the new status to a friendly Hungarian sentence that
        // anchors the email body. Lives here (not in Blade) because
        // template logic with `@if` chains is harder to keep aligned.
        $headline = match ($this->newStatus) {
            'processing' => 'Megkezdtük a rendelésed összeállítását.',
            'shipped'    => 'Útnak indítottuk a rendelésed.',
            'arrived'    => 'Rendelésed megérkezett, jó illatozást!',
            'canceled'   => 'Rendelésed lemondását rögzítettük.',
            'refunded'   => 'A rendelésed összegét visszautaltuk.',
            default      => 'Rendelésed státusza frissült.',
        };

        $ordersUrl = rtrim(
            config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')),
            '/'
        ) . '/account/orders';

        return new Content(
            markdown: 'emails.order-status-changed',
            with: [
                'order'      => $this->order,
                'newStatus'  => $this->newStatus,
                'headline'   => $headline,
                'ordersUrl'  => $ordersUrl,
            ],
        );
    }
}
