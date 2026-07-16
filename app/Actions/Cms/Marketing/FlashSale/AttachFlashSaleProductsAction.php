<?php

namespace App\Actions\Cms\Marketing\FlashSale;

use App\Models\FlashSale\FlashSale;
use App\Models\FlashSale\FlashSaleProduct;
use App\Models\PPOB\PPOBProduct;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AttachFlashSaleProductsAction
{
    /**
     * Handle the action.
     */
    public function handle(FlashSale $flashSale, array $data): void
    {
        $productIds = $data['product_ids'];

        $this->guardAgainstConflictingSales($flashSale, $productIds);

        $products = PPOBProduct::whereIn('id', $productIds)->get()->keyBy('id');

        $originalPrice = ! empty($data['original_price'])
            ? (int) currencyToNumber($data['original_price'])
            : null;

        foreach ($productIds as $productId) {
            $product = $products->get($productId);

            if (! $product) {
                continue;
            }

            $flashPrice = $data['pricing_type'] === 'percent'
                ? (int) round(($originalPrice ?? $product->sell_price) * (1 - ($data['discount_percent'] / 100)))
                : (int) currencyToNumber($data['flash_price']);

            FlashSaleProduct::updateOrCreate(
                ['flash_sale_id' => $flashSale->id, 'p_p_o_b_product_id' => $productId],
                [
                    'pricing_type' => $data['pricing_type'],
                    'discount_percent' => $data['pricing_type'] === 'percent' ? $data['discount_percent'] : null,
                    'original_price' => $originalPrice,
                    'flash_price' => $flashPrice,
                    'flash_stock' => $data['flash_stock'],
                    'status' => 'active',
                ]
            );
        }

        Cache::forget('flash_sale:active');
    }

    /**
     * A product can't be in more than one Scheduled/Active flash sale at a time.
     */
    protected function guardAgainstConflictingSales(FlashSale $flashSale, array $productIds): void
    {
        $conflicting = FlashSaleProduct::whereIn('p_p_o_b_product_id', $productIds)
            ->where('flash_sale_id', '!=', $flashSale->id)
            ->whereHas('flashSale', fn ($q) => $q->whereIn('status', ['scheduled', 'active']))
            ->with('product:id,name')
            ->get();

        if ($conflicting->isNotEmpty()) {
            throw ValidationException::withMessages([
                'product_ids' => 'Sudah ada di Flash Sale lain yang scheduled/active: '
                    .$conflicting->pluck('product.name')->implode(', '),
            ]);
        }
    }
}
