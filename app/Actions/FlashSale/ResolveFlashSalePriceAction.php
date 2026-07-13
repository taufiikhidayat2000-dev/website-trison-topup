<?php

namespace App\Actions\FlashSale;

use App\Models\FlashSale\FlashSale;
use App\Models\FlashSale\FlashSaleProduct;

class ResolveFlashSalePriceAction
{
    /**
     * Resolve the flash-sale price for a product, if it's currently in a
     * purchasable flash sale with stock remaining. Returns null otherwise,
     * in which case the caller should fall back to the product's normal price.
     *
     * @return array{flash_sale_id: int, flash_sale_product_id: int, flash_price: int}|null
     */
    public function handle(int $productId): ?array
    {
        $sale = FlashSale::cachedVisible();

        if (! $sale || ! $sale->isPurchasable()) {
            return null;
        }

        $flashSaleProduct = $sale->products->firstWhere('p_p_o_b_product_id', $productId);

        if (! $flashSaleProduct || $flashSaleProduct->status === 'sold_out') {
            return null;
        }

        // Live, uncached remaining-stock check - a hard sold-out gate is worth
        // one extra query; the 60s cache above is for the price/eligibility lookup only.
        $fresh = FlashSaleProduct::find($flashSaleProduct->id);

        if (! $fresh || $fresh->remaining_stock <= 0) {
            return null;
        }

        return [
            'flash_sale_id' => $sale->id,
            'flash_sale_product_id' => $flashSaleProduct->id,
            'flash_price' => $flashSaleProduct->flash_price,
        ];
    }
}
