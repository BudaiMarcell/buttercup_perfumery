<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public string $verifyUrl;

    public function __construct(User $user)
    {
        $this->user = $user;

        $this->verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id'   => $user->id,
                'hash' => sha1($user->email),
            ]
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Erősítsd meg az e-mail címed - Buttercup Perfumery',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.verify',
            with: [
                'name'      => $this->user->name,
                'verifyUrl' => $this->verifyUrl,
            ],
        );
    }
}
