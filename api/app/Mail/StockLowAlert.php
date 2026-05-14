<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Internal alert to admins when a product's stock crosses a low-water
 * mark after an order is placed (or any other decrement).
 *
 * This is NOT customer-facing — it's a quiet ops nudge to "go restock
 * the warehouse before we oversell". Send-frequency control lives in
 * the dispatcher (OrderController::store) so we don't ship multiple
 * alerts per stock movement.
 */
class StockLowAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Product $product;
    public int $threshold;

    public function __construct(Product $product, int $threshold)
    {
        $this->product   = $product;
        $this->threshold = $threshold;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠ Készlet alacsony: {$this->product->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.stock-low',
            with: [
                'product'   => $this->product,
                'threshold' => $this->threshold,
            ],
        );
    }
}
