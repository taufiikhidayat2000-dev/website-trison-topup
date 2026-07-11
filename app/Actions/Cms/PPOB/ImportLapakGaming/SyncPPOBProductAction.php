<?php

namespace App\Actions\Cms\PPOB\ImportLapakGaming;

use App\Models\PPOB\PPOBBrand;
use App\Models\PPOB\PPOBCategory;
use App\Models\PPOB\PPOBProduct;
use App\Services\LapakGamingService;

class SyncPPOBProductAction
{
    public function __construct(
        public readonly LapakGamingService $lapakGamingService,
    ) {}

    /**
     * Handle the action.
     */
    public function handle(): void
    {
        $products = $this->lapakGamingService->getAllProducts();

        foreach ($products as $productData) {
            $category = PPOBCategory::firstOrCreate(
                ['name' => $productData['category']],
                [
                    'description' => 'Imported from LapakGaming',
                    'status' => true,
                ],
            );
            $brand = PPOBBrand::firstOrCreate(
                [
                    'p_p_o_b_category_id' => $category->id,
                    'name' => $productData['brand'],
                ],
                [
                    'provider' => 'lapakgaming',
                    'description' => 'Imported from LapakGaming',
                    'featured' => false,
                    'order' => PPOBBrand::max('order') + 1,
                    'settings' => [
                        'type' => 'id',
                        'label_id' => 'uid',
                    ],
                    'status' => true,
                ],
            );
            PPOBProduct::updateOrCreate(
                [
                    'p_p_o_b_brand_id' => $brand->id,
                    'sku' => $productData['product_code'],
                ],
                [
                    'provider' => 'lapakgaming',
                    'name' => $productData['product_name'],
                    'description' => 'Imported from LapakGaming',
                    'buy_price' => $productData['price'],
                    'sell_price' => $productData['price'] * 1.1, // 10% markup
                    'status' => true,
                ],
            );
        }
    }
}
