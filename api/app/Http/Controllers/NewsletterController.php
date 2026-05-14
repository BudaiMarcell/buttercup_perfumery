<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    /**
     * Public newsletter signup endpoint, called by the homepage form.
     *
     * Behaviour notes:
     *   - We use updateOrCreate so a re-submission of the same email
     *     doesn't 409. If the user previously unsubscribed, this is
     *     also how they re-subscribe (we null out unsubscribed_at).
     *   - We don't email-confirm yet — the project's spam risk is
     *     low and the UX is "type, press, done". A double-opt-in flow
     *     can land later as a follow-up if abuse picks up.
     *   - Endpoint is throttled (configured in routes) so a script
     *     can't flood the table.
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email'  => 'required|email|max:255',
            'source' => 'sometimes|string|max:64',
        ]);

        $subscriber = NewsletterSubscriber::updateOrCreate(
            ['email' => mb_strtolower($validated['email'])],
            [
                'subscribed_at'   => now(),
                'unsubscribed_at' => null,
                'source'          => $validated['source'] ?? 'homepage',
            ]
        );

        return response()->json([
            'message' => 'Köszönjük! Felvettünk a listára.',
            'id'      => $subscriber->id,
        ], 201);
    }

    /**
     * Soft-unsubscribe. Email-based, no token required for now (would
     * be added when we ship a real unsubscribe link in the marketing
     * mails). Doesn't reveal whether the address was in the list.
     */
    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        NewsletterSubscriber::where('email', mb_strtolower($validated['email']))
            ->update(['unsubscribed_at' => now()]);

        return response()->json([
            'message' => 'Sikeresen leiratkoztál a hírlevélről.',
        ]);
    }
}
