<?php

// Creates the table that temporarily holds stock while customer pays
// This prevents overselling without touching the products.stock column
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ProductReservationStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkout_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->timestamp('expires_at');
            $table->string('status')->default(ProductReservationStatus::ACTIVE->value);
            $table->timestamps();

            $table->index(['product_id', 'status', 'expires_at'], 'idx_available_stock');            
            $table->index(['checkout_session_id', 'status'], 'idx_session_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_reservations');
    }
};