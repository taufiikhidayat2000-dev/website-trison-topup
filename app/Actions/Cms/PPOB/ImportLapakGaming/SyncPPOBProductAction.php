<?php

namespace App\Actions\Cms\PPOB\ImportLapakGaming;

use App\Models\PPOB\PPOBBrand;
use App\Models\PPOB\PPOBCategory;
use App\Models\PPOB\PPOBProduct;
use App\Services\LapakGamingService;
use Illuminate\Support\Collection;

class SyncPPOBProductAction
{
    public function __construct(
        public readonly LapakGamingService $lapakGamingService,
    ) {}

    /**
     * Handle the action.
     *
     * LapakGaming has no "brand" tier of its own: a category IS a game/app
     * (e.g. code "AR" = "Atlantica Rebirth"), directly containing products.
     * So category -> PPOBBrand and every synced brand shares one umbrella
     * PPOBCategory, mirroring how ImportDigiflazz\SyncPPOBProductAction
     * shapes category -> brand -> product.
     */
    public function handle(): void
    {
        $categories = collect($this->lapakGamingService->getCategories())->keyBy('code');
        $products = collect($this->lapakGamingService->getAllProducts());

        $umbrellaCategory = PPOBCategory::firstOrCreate(
            ['name' => 'Games'],
            [
                'description' => 'Imported from LapakGaming',
                'status' => true,
            ],
        );

        foreach ($products->groupBy('category_code') as $categoryCode => $categoryProducts) {
            $categoryInfo = $categories->get($categoryCode);

            if (! $categoryInfo) {
                continue;
            }

            $brand = PPOBBrand::firstOrCreate(
                [
                    'p_p_o_b_category_id' => $umbrellaCategory->id,
                    'name' => $categoryInfo['name'],
                ],
                [
                    'provider' => 'lapakgaming',
                    'description' => 'Imported from LapakGaming',
                    'featured' => false,
                    'order' => PPOBBrand::max('order') + 1,
                    'settings' => $this->buildBrandSettings($categoryInfo),
                    'status' => true,
                ],
            );

            foreach ($categoryProducts as $productData) {
                PPOBProduct::updateOrCreate(
                    [
                        'p_p_o_b_brand_id' => $brand->id,
                        'sku' => $productData['code'],
                    ],
                    [
                        'provider' => 'lapakgaming',
                        'name' => $productData['name'],
                        'description' => 'Imported from LapakGaming',
                        'buy_price' => $productData['price'],
                        'sell_price' => $productData['price'] * 1.1, // 10% markup
                        'status' => ($productData['status'] ?? null) === 'available',
                    ],
                );
            }
        }
    }

    /**
     * Map LapakGaming's category `forms`/`servers` onto the checkout
     * settings shape the storefront understands (see
     * resources/js/pages/cms/ppob/ppob-brand/Create.vue): 'id' for a
     * plain user-id form, 'id+server' when the category also asks for
     * additional_id (zone/server).
     */
    protected function buildBrandSettings(array $categoryInfo): array
    {
        $forms = collect($categoryInfo['forms'] ?? []);
        $hasAdditionalId = $forms->contains(fn ($form) => ($form['name'] ?? null) === 'additional_id');

        if (! $hasAdditionalId) {
            return [
                'type' => 'id',
                'label_id' => 'User ID',
            ];
        }

        return [
            'type' => 'id+server',
            'label_id' => 'User ID',
            'label_server' => 'Server',
            'server_list' => $this->mapServerList($categoryInfo['servers'] ?? []),
        ];
    }

    protected function mapServerList(array $servers): array
    {
        return (new Collection($servers))
            ->map(fn ($server) => [
                'value' => $server['value'] ?? '',
                'name' => $server['name'] ?? '',
            ])
            ->values()
            ->all();
    }
}
