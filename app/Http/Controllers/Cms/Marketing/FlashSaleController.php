<?php

namespace App\Http\Controllers\Cms\Marketing;

use App\Actions\Cms\Marketing\FlashSale\DeleteFlashSaleAction;
use App\Actions\Cms\Marketing\FlashSale\StoreFlashSaleAction;
use App\Actions\Cms\Marketing\FlashSale\UpdateFlashSaleAction;
use App\Enums\FlashSaleStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\Marketing\FlashSale\StoreFlashSaleRequest;
use App\Http\Requests\Cms\Marketing\FlashSale\UpdateFlashSaleRequest;
use App\Models\FlashSale\FlashSale;
use App\Models\FlashSale\FlashSaleUse;
use App\Models\Order\Order;
use App\Traits\WithGetFilterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FlashSaleController extends Controller
{
    use WithGetFilterData;

    protected string $resource = FlashSale::class;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('view'.$this->resource);

        $order = $request?->order ?? 'desc';
        $orderBy = $request?->orderBy ?? 'created_at';
        $paginate = $request?->paginate ?? 10;
        $searchBySpecific = $request?->searchBySpecific ?? '';
        $search = $request?->search ?? '';

        $model = $this->getDataWithFilter(
            model: FlashSale::query()->withCount('products'),
            searchBy: [
                'title',
                'subtitle',
            ],
            order: $order,
            orderBy: $orderBy,
            paginate: $paginate,
            searchBySpecific: $searchBySpecific,
            s: $search,
        );

        return inertia('cms/marketing/flash-sale/Index', [
            'data' => $model,
            'order' => $order,
            'orderBy' => $orderBy,
            'paginate' => $paginate,
            'searchBySpecific' => $searchBySpecific,
            'search' => $search,
            'resource' => $this->resource,
            'stats' => $this->stats(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create'.$this->resource);

        return inertia('cms/marketing/flash-sale/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFlashSaleRequest $request, StoreFlashSaleAction $action)
    {
        Gate::authorize('create'.$this->resource);

        $action->handle($request->validated());

        return to_route('cms.marketing.flash-sales.index')->with('success', 'Flash Sale created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FlashSale $flashSale)
    {
        Gate::authorize('update'.$this->resource);

        $flashSale->load(['products.product.media', 'products.product.brand']);
        $flashSale->icon_image_url = $flashSale->getFirstMediaUrl('icon');
        $flashSale->banner_url = $flashSale->getFirstMediaUrl('banner');

        return inertia('cms/marketing/flash-sale/Edit', [
            'flashSale' => $flashSale,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFlashSaleRequest $request, FlashSale $flashSale, UpdateFlashSaleAction $action)
    {
        Gate::authorize('update'.$this->resource);

        $action->handle($flashSale, $request->validated());

        return to_route('cms.marketing.flash-sales.index')->with('success', 'Flash Sale updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FlashSale $flashSale, DeleteFlashSaleAction $action)
    {
        Gate::authorize('delete'.$this->resource);

        $action->handle($flashSale);

        return back()->with('success', 'Flash Sale deleted successfully.');
    }

    /**
     * Dashboard summary stats for the Flash Sale index page.
     */
    protected function stats(): array
    {
        $activeSales = FlashSale::where('status', FlashSaleStatusEnum::ACTIVE)->with('products')->get();

        $totalProducts = $activeSales->sum(fn ($sale) => $sale->products->count());
        $totalSold = $activeSales->sum(fn ($sale) => $sale->products->sum('sold'));
        $remainingStock = $activeSales->sum(fn ($sale) => $sale->products->sum('remaining_stock'));
        $nearestEnd = $activeSales->sortBy('end_time')->first();

        return [
            'active_count' => $activeSales->count(),
            'total_products' => $totalProducts,
            'total_sold' => $totalSold,
            'revenue' => (int) FlashSaleUse::query()
                ->where('usable_type', Order::class)
                ->whereIn('usable_id', Order::where('payment_status', PaymentStatusEnum::SETTLEMENT)->pluck('id'))
                ->sum('flash_price'),
            'remaining_stock' => $remainingStock,
            'nearest_end_time' => $nearestEnd?->end_time,
        ];
    }
}
