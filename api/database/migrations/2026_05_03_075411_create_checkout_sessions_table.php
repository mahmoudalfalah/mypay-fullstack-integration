<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\CheckoutSessionStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('mypay_payment_id')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->json('order_data');
            $table->json('order_items');
            $table->string('status')->default(CheckoutSessionStatus::INITIATED->value);
            $table->timestamp('expires_at');
            $table->boolean('clear_cart')->default(false);
            $table->timestamp('cart_cleared_at')->nullable();
            $table->timestamps();

            $table->index('mypay_payment_id');
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_sessions');
    }
};