<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Your password was just changed" notification.
 *
 * Fires AFTER a successful password reset (or admin-initiated change),
 * to the same user. Purpose: if the change wasn't them, they can see it
 * within seconds and request a fresh reset / contact us. We don't tell
 * the email recipient anything sensitive (no IP, no device) — just
 * "this happened at this time".
 */
class PasswordChangedNotice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public string $changedAt;

    public function __construct(User $user)
    {
        $this->user      = $user;
        // Format on construct so the queue worker's "now" doesn't drift
        // from the actual change time if the job sits in the queue.
        $this->changedAt = now()->format('Y. m. d. H:i');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Jelszavad módosítva - Buttercup Perfumery',
        );
    }

    public function content(): Content
    {
        $forgotUrl = rtrim(
            config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')),
            '/'
        ) . '/login';

        return new Content(
            markdown: 'emails.password-changed',
            with: [
                'name'      => $this->user->name,
                'changedAt' => $this->changedAt,
                'forgotUrl' => $forgotUrl,
            ],
        );
    }
}
