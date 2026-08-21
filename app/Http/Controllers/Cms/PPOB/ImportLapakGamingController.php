<?php

namespace App\Http\Controllers\Cms\PPOB;

use App\Actions\Cms\PPOB\ImportLapakGaming\SyncPPOBProductAction;
use App\Http\Controllers\Controller;
use App\Models\PPOB\PPOBProduct;
use App\Services\LapakGamingService;
use App\Traits\WithGetFilterData;
use Illuminate\Support\Facades\Gate;

class ImportLapakGamingController extends Controller
{
    use WithGetFilterData;

    protected string $resource = PPOBProduct::class;

    public function __construct(
        public readonly LapakGamingService $lapakGamingService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('view'.$this->resource);

        $enabled = $this->lapakGamingService->isEnabled();
        $categories = $enabled ? collect($this->lapakGamingService->getCategories())->keyBy('code') : collect();
        $products = $enabled ? collect($this->lapakGamingService->getAllProducts()) : collect();

        // LapakGaming has no brand tier: a category IS the game/app, so it
        // maps directly to a PPOBBrand (see ImportLapakGaming\SyncPPOBProductAction).
        $groupedProducts = $products->groupBy('category_code')->map(function ($items, $categoryCode) use ($categories) {
            return [
                'name' => $categories->get($categoryCode)['name'] ?? $categoryCode,
                'products' => $items->map(fn ($item) => [
                    'product_name' => $item['name'],
                    'buyer_sku_code' => $item['code'],
                    'price' => $item['price'],
                    'status' => $item['status'] ?? null,
                ])->values(),
            ];
        })->values();

        return inertia('cms/ppob/import-lapakgaming/Index', [
            'products' => $groupedProducts,
            'enabled' => $enabled,
        ]);
    }

    /**
     * Sync newly imported LapakGaming products.
     */
    public function sync(SyncPPOBProductAction $action)
    {
        Gate::authorize('create'.$this->resource);

        abort_unless($this->lapakGamingService->isEnabled(), 422, 'LapakGaming belum aktif.');

        $action->handle();

        return back();
    }
}
