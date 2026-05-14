<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order->load(['items.product', 'address', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rendelésed visszaigazolása - #' . $this->order->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order-placed',
            with: [
                'order' => $this->order,
            ],
        );
    }
}
