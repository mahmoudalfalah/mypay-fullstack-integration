<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductStockReservation;
use App\Models\CheckoutSession;
use App\Enums\CheckoutSessionStatus;
use App\Enums\ProductReservationStatus;

class ReleaseExpiredStock extends Command
{
    protected $signature = 'stock:release-expired';
    protected $description = 'Release stock for expired checkout sessions';

    public function handle(): void
    {
        $now = now();

        $expiredSessionIds = CheckoutSession::where('expires_at', '<=', $now)
            ->whereIn('status', [
                CheckoutSessionStatus::INITIATED->value,
                CheckoutSessionStatus::PENDING->value,
            ])
            ->pluck('id');

        if ($expiredSessionIds->isEmpty()) {
            $this->info("No expired sessions to process.");
            return;
        }

        CheckoutSession::whereIn('id', $expiredSessionIds)
            ->update(['status' => CheckoutSessionStatus::EXPIRED->value]);

        ProductStockReservation::whereIn('checkout_session_id', $expiredSessionIds)
            ->where('status', ProductReservationStatus::ACTIVE->value)
            ->update(['status' => ProductReservationStatus::EXPIRED->value]);

        $this->info("Released stock for {$expiredSessionIds->count()} expired sessions.");
    }
}