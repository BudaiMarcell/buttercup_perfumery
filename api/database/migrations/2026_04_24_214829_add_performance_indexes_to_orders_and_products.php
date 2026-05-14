<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->index(['user_id', 'created_at'], 'orders_user_id_created_at_index');

            $table->index('payment_status', 'orders_payment_status_index');
        });

        Schema::table('products', function (Blueprint $table) {

            $table->index('is_active', 'products_is_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_id_created_at_index');
            $table->dropIndex('orders_payment_status_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_is_active_index');
        });
    }
};
