<?php

namespace App\Actions\Cms\Marketing\FlashSale;

use App\Models\FlashSale\FlashSaleProduct;
use Illuminate\Support\Facades\Cache;

class UpdateFlashSaleProductAction
{
    /**
     * Handle the action.
     */
    public function handle(FlashSaleProduct $flashSaleProduct, array $data): FlashSaleProduct
    {
        $product = $flashSaleProduct->product;

        $originalPrice = ! empty($data['original_price'])
            ? (int) currencyToNumber($data['original_price'])
            : null;

        $flashPrice = $data['pricing_type'] === 'percent'
            ? (int) round(($originalPrice ?? $product->sell_price) * (1 - ($data['discount_percent'] / 100)))
            : (int) currencyToNumber($data['flash_price']);

        $flashSaleProduct->update([
            'pricing_type' => $data['pricing_type'],
            'discount_percent' => $data['pricing_type'] === 'percent' ? $data['discount_percent'] : null,
            'original_price' => $originalPrice,
            'flash_price' => $flashPrice,
            'flash_stock' => $data['flash_stock'],
            'status' => $data['status'],
        ]);

        Cache::forget('flash_sale:active');

        return $flashSaleProduct;
    }
}
