<?php

namespace App\Http\Controllers\Cms\Marketing;

use App\Actions\Cms\Marketing\FlashSale\AttachFlashSaleProductsAction;
use App\Actions\Cms\Marketing\FlashSale\DetachFlashSaleProductAction;
use App\Actions\Cms\Marketing\FlashSale\UpdateFlashSaleProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\Marketing\FlashSale\AttachFlashSaleProductsRequest;
use App\Http\Requests\Cms\Marketing\FlashSale\UpdateFlashSaleProductRequest;
use App\Models\FlashSale\FlashSale;
use App\Models\FlashSale\FlashSaleProduct;
use App\Models\PPOB\PPOBProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FlashSaleProductController extends Controller
{
    protected string $resource = FlashSaleProduct::class;

    /**
     * Search products to attach to a flash sale, flagging ones already in
     * another scheduled/active sale so the picker can disable them.
     */
    public function search(Request $request, FlashSale $flashSale)
    {
        Gate::authorize('view'.$this->resource);

        $products = PPOBProduct::query()
            ->with('brand:id,name', 'productCategory:id,name', 'media')
            ->when($request->search, fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->when($request->brand_id, fn ($q) => $q->where('p_p_o_b_brand_id', $request->brand_id))
            ->when($request->category_id, fn ($q) => $q->where('p_p_o_b_product_category_id', $request->category_id))
            ->limit(50)
            ->get();

        $conflictingIds = FlashSaleProduct::whereIn('p_p_o_b_product_id', $products->pluck('id'))
            ->where('flash_sale_id', '!=', $flashSale->id)
            ->whereHas('flashSale', fn ($q) => $q->whereIn('status', ['scheduled', 'active']))
            ->pluck('p_p_o_b_product_id');

        $products->each(function ($product) use ($conflictingIds) {
            $product->already_in_conflicting_sale = $conflictingIds->contains($product->id);
        });

        return response()->json(['data' => $products]);
    }

    /**
     * Attach one or more products to the flash sale.
     */
    public function store(AttachFlashSaleProductsRequest $request, FlashSale $flashSale, AttachFlashSaleProductsAction $action)
    {
        Gate::authorize('create'.$this->resource);

        $action->handle($flashSale, $request->validated());

        return back()->with('success', 'Products added to Flash Sale.');
    }

    /**
     * Update pricing/stock/status for a single attached product.
     */
    public function update(UpdateFlashSaleProductRequest $request, FlashSale $flashSale, FlashSaleProduct $flashSaleProduct, UpdateFlashSaleProductAction $action)
    {
        Gate::authorize('update'.$this->resource);

        $action->handle($flashSaleProduct, $request->validated());

        return back()->with('success', 'Flash Sale product updated.');
    }

    /**
     * Remove a product from the flash sale.
     */
    public function destroy(FlashSale $flashSale, FlashSaleProduct $flashSaleProduct, DetachFlashSaleProductAction $action)
    {
        Gate::authorize('delete'.$this->resource);

        $action->handle($flashSaleProduct);

        return back()->with('success', 'Product removed from Flash Sale.');
    }
}
