<?php

namespace App\Actions\Reseller;

class ResolveResellerPriceAction
{
    /**
     * Resolve the reseller price for a base sell price: a flat percentage
     * discount read from settings (default 2%), computed on the fly so it
     * never goes stale when sell_price or the discount percentage changes -
     * nothing per-product is stored.
     *
     * @return array{percent: float, reseller_price: int, discount_amount: int}
     */
    public function handle(int $sellPrice): array
    {
        $percent = (float) (getSetting('reseller_discount_percent') ?? 2);
        $percent = max(0, min(100, $percent));

        $resellerPrice = (int) round($sellPrice * (1 - $percent / 100));

        return [
            'percent' => $percent,
            'reseller_price' => $resellerPrice,
            'discount_amount' => $sellPrice - $resellerPrice,
        ];
    }
}
