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
 * Password-reset email.
 *
 * Triggered by Laravel's password broker via the
 * User::sendPasswordResetNotification() override. The plaintext token comes
 * from the broker (and is hashed inside the password_reset_tokens table —
 * the URL contains the *plaintext* version which is what the user submits
 * back when picking a new password). Link lives 60 minutes (config/auth.php
 * → passwords.users.expire).
 */
class ResetPasswordEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public string $resetUrl;

    public function __construct(User $user, string $resetUrl)
    {
        $this->user     = $user;
        $this->resetUrl = $resetUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Jelszó visszaállítása - Buttercup Perfumery',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reset-password',
            with: [
                'name'     => $this->user->name,
                'resetUrl' => $this->resetUrl,
            ],
        );
    }
}
