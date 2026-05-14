<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Newsletter subscribers — single-source-of-truth for the homepage
 * Subscribe form. Anonymous (no link to `users`), so a non-customer
 * can sign up, and customers can sign up with a different inbox if
 * they want to keep marketing mail out of their main account email.
 *
 * `unsubscribed_at` is the soft-unsubscribe column rather than a
 * destructive delete — keeping the row means "this address already
 * signed up once" remains queryable, useful for compliance audits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->unique();
            $table->timestamp('subscribed_at')->useCurrent();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('source', 64)->nullable();
            $table->timestamps();

            $table->index('unsubscribed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
