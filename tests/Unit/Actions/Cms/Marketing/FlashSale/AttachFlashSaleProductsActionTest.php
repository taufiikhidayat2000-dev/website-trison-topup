<?php

use App\Actions\Cms\Marketing\FlashSale\AttachFlashSaleProductsAction;
use App\Models\FlashSale\FlashSale;
use App\Models\FlashSale\FlashSaleProduct;
use App\Models\PPOB\PPOBBrand;
use App\Models\PPOB\PPOBCategory;
use App\Models\PPOB\PPOBProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Regression coverage for the original_price division-by-zero bug: weak
 * validation ('numeric' only, no min:1) let 0/"0" through, and BrandController
 * later divided by that stored original_price. These tests exercise the
 * Action layer directly to lock in what actually gets persisted.
 */
function createAttachFlashSaleProduct(int $sellPrice = 15000): PPOBProduct
{
    $category = PPOBCategory::create(['name' => 'Attach Test Category', 'status' => true]);

    $brand = PPOBBrand::create([
        'p_p_o_b_category_id' => $category->id,
        'name' => 'Attach Test Brand',
        'status' => true,
    ]);

    return PPOBProduct::create([
        'p_p_o_b_brand_id' => $brand->id,
        'name' => 'Attach Test Product',
        'sku' => 'ATTACH-SKU-'.uniqid(),
        'buy_price' => (int) round($sellPrice * 0.8),
        'sell_price' => $sellPrice,
        'status' => true,
    ]);
}

function createAttachTestFlashSale(): FlashSale
{
    return FlashSale::create([
        'title' => 'Attach Test Flash Sale',
        'start_time' => now(),
        'end_time' => now()->addDay(),
    ]);
}

test('manual pricing with a valid original_price stores it on the created flash sale product', function () {
    $product = createAttachFlashSaleProduct();
    $flashSale = createAttachTestFlashSale();
    $action = new AttachFlashSaleProductsAction;

    $action->handle($flashSale, [
        'product_ids' => [$product->id],
        'pricing_type' => 'manual',
        'original_price' => 20000,
        'flash_price' => 15000,
        'flash_stock' => 10,
    ]);

    $flashSaleProduct = FlashSaleProduct::where('flash_sale_id', $flashSale->id)
        ->where('p_p_o_b_product_id', $product->id)
        ->first();

    expect($flashSaleProduct)->not->toBeNull();
    expect($flashSaleProduct->original_price)->toBe(20000);
});

test('manual pricing with original_price omitted stores null, falling back to sell_price at display time', function () {
    $product = createAttachFlashSaleProduct();
    $flashSale = createAttachTestFlashSale();
    $action = new AttachFlashSaleProductsAction;

    $action->handle($flashSale, [
        'product_ids' => [$product->id],
        'pricing_type' => 'manual',
        'flash_price' => 15000,
        'flash_stock' => 10,
    ]);

    $flashSaleProduct = FlashSaleProduct::where('flash_sale_id', $flashSale->id)
        ->where('p_p_o_b_product_id', $product->id)
        ->first();

    expect($flashSaleProduct->original_price)->toBeNull();
});

test('percent pricing with original_price omitted computes flash_price from the product sell_price', function () {
    $product = createAttachFlashSaleProduct();
    $flashSale = createAttachTestFlashSale();
    $action = new AttachFlashSaleProductsAction;

    $action->handle($flashSale, [
        'product_ids' => [$product->id],
        'pricing_type' => 'percent',
        'discount_percent' => 10,
        'flash_stock' => 5,
    ]);

    $flashSaleProduct = FlashSaleProduct::where('flash_sale_id', $flashSale->id)
        ->where('p_p_o_b_product_id', $product->id)
        ->first();

    expect($flashSaleProduct->original_price)->toBeNull();
    expect($flashSaleProduct->flash_price)->toBe((int) round(15000 * 0.9));
});

test('percent pricing with a marked-up original_price computes flash_price from that markup, not sell_price', function () {
    $product = createAttachFlashSaleProduct();
    $flashSale = createAttachTestFlashSale();
    $action = new AttachFlashSaleProductsAction;

    $action->handle($flashSale, [
        'product_ids' => [$product->id],
        'pricing_type' => 'percent',
        'discount_percent' => 20,
        'original_price' => 30000,
        'flash_stock' => 5,
    ]);

    $flashSaleProduct = FlashSaleProduct::where('flash_sale_id', $flashSale->id)
        ->where('p_p_o_b_product_id', $product->id)
        ->first();

    expect($flashSaleProduct->original_price)->toBe(30000);
    expect($flashSaleProduct->flash_price)->toBe((int) round(30000 * 0.8));
});

/**
 * The bulk "Tambah Produk" flow only offers a single original_price/discount
 * pair for the whole selection - this documents the current (accepted)
 * behavior that it applies uniformly even when selected products have
 * different real sell_price values, rather than per-product markup.
 */
test('bulk attach applies the same original_price and discount to every selected product regardless of its own sell_price', function () {
    $cheapProduct = createAttachFlashSaleProduct(sellPrice: 10000);
    $expensiveProduct = createAttachFlashSaleProduct(sellPrice: 200000);
    $flashSale = createAttachTestFlashSale();
    $action = new AttachFlashSaleProductsAction;

    $action->handle($flashSale, [
        'product_ids' => [$cheapProduct->id, $expensiveProduct->id],
        'pricing_type' => 'percent',
        'discount_percent' => 20,
        'original_price' => 50000,
        'flash_stock' => 5,
    ]);

    $rows = FlashSaleProduct::where('flash_sale_id', $flashSale->id)
        ->whereIn('p_p_o_b_product_id', [$cheapProduct->id, $expensiveProduct->id])
        ->get()
        ->keyBy('p_p_o_b_product_id');

    // Both rows get the identical markup and computed flash_price, even
    // though the cheap product's real sell_price (10000) is now marked up
    // 5x and the expensive product's (200000) is marked down to 50000.
    expect($rows[$cheapProduct->id]->original_price)->toBe(50000);
    expect($rows[$expensiveProduct->id]->original_price)->toBe(50000);
    expect($rows[$cheapProduct->id]->flash_price)->toBe((int) round(50000 * 0.8));
    expect($rows[$expensiveProduct->id]->flash_price)->toBe((int) round(50000 * 0.8));
});
