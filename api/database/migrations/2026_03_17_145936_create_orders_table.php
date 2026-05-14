<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();

        $table->enum('status', [
            'pending',
            'processing',
            'shipped',
            'arrived',
            'canceled', 
            'refunded'
        ])->default('pending')->index();

        $table->decimal('total_amount', 10, 2);
        $table->string('payment_method')->nullable();
        $table->enum('payment_status', [
            'pending',
            'processing',
            'paid',
            'failed',
            'refunded'
        ])->default('pending');
        $table->text('notes')->nullable();
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
