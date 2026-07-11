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

        $products = $this->lapakGamingService->isEnabled()
            ? $this->lapakGamingService->getAllProducts()
            : [];

        $groupedProducts = collect($products)->groupBy('category')->map(function ($items, $category) {
            return [
                'name' => $category,
                'brands' => $items->groupBy('brand')->map(function ($brandItems, $brand) {
                    return [
                        'name' => $brand,
                        'products' => $brandItems->map(fn ($item) => [
                            'product_name' => $item['product_name'],
                            'buyer_sku_code' => $item['product_code'],
                            'price' => $item['price'],
                        ])->values(),
                    ];
                })->values(),
            ];
        })->values();

        return inertia('cms/ppob/import-lapakgaming/Index', [
            'products' => $groupedProducts,
            'enabled' => $this->lapakGamingService->isEnabled(),
        ]);
    }

    /**
     * Sync newly imported LapakGaming products.
     */
    public function sync(SyncPPOBProductAction $action)
    {
        Gate::authorize('create'.$this->resource);

        $action->handle();

        return back();
    }
}
