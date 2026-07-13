<?php

namespace App\Actions\FlashSale;

use App\Models\FlashSale\FlashSaleUse;
use App\Models\Order\Order;
use Illuminate\Support\Facades\Cache;

class SettleFlashSaleStockAction
{
    /**
     * Decrement flash-sale stock once a payment is confirmed settled. Called
     * from every payment-success path (balance, LinkQu callback, Midtrans
     * callback) so stock is only ever touched on confirmed success - a
     * failed/expired/cancelled payment never decrements anything.
     */
    public function handle(Order $order): void
    {
        if (! $order->flash_sale_id) {
            return;
        }

        $use = FlashSaleUse::where('usable_type', Order::class)
            ->where('usable_id', $order->id)
            ->first();

        if (! $use) {
            return;
        }

        $use->flashSaleProduct->decrementStock(1);

        Cache::forget('flash_sale:active');
    }
}
