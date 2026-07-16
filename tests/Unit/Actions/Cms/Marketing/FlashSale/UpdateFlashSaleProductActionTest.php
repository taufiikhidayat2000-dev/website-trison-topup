<?php

use App\Actions\Cms\Marketing\FlashSale\UpdateFlashSaleProductAction;
use App\Models\FlashSale\FlashSale;
use App\Models\FlashSale\FlashSaleProduct;
use App\Models\PPOB\PPOBBrand;
use App\Models\PPOB\PPOBCategory;
use App\Models\PPOB\PPOBProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Regression coverage for the original_price division-by-zero bug (see
 * AttachFlashSaleProductsActionTest), mirrored for the update path.
 */
function createUpdateFlashSaleProduct(): PPOBProduct
{
    $category = PPOBCategory::create(['name' => 'Update Test Category', 'status' => true]);

    $brand = PPOBBrand::create([
        'p_p_o_b_category_id' => $category->id,
        'name' => 'Update Test Brand',
        'status' => true,
    ]);

    return PPOBProduct::create([
        'p_p_o_b_brand_id' => $brand->id,
        'name' => 'Update Test Product',
        'sku' => 'UPDATE-SKU-'.uniqid(),
        'buy_price' => 10000,
        'sell_price' => 15000,
        'status' => true,
    ]);
}

function createUpdateTestFlashSaleProductRow(): FlashSaleProduct
{
    $product = createUpdateFlashSaleProduct();

    $flashSale = FlashSale::create([
        'title' => 'Update Test Flash Sale',
        'start_time' => now(),
        'end_time' => now()->addDay(),
    ]);

    return FlashSaleProduct::create([
        'flash_sale_id' => $flashSale->id,
        'p_p_o_b_product_id' => $product->id,
        'pricing_type' => 'manual',
        'original_price' => null,
        'flash_price' => 12000,
        'flash_stock' => 10,
        'status' => 'active',
    ]);
}

test('manual pricing with a valid original_price updates it on the flash sale product', function () {
    $flashSaleProduct = createUpdateTestFlashSaleProductRow();
    $action = new UpdateFlashSaleProductAction;

    $result = $action->handle($flashSaleProduct, [
        'pricing_type' => 'manual',
        'original_price' => 30000,
        'flash_price' => 18000,
        'flash_stock' => 10,
        'status' => 'active',
    ]);

    expect($result->original_price)->toBe(30000);
    $flashSaleProduct->refresh();
    expect($flashSaleProduct->original_price)->toBe(30000);
});

test('manual pricing with original_price omitted stores null, falling back to sell_price at display time', function () {
    $flashSaleProduct = createUpdateTestFlashSaleProductRow();
    // Seed a non-null value first to prove the update actually clears it.
    $flashSaleProduct->update(['original_price' => 25000]);
    $action = new UpdateFlashSaleProductAction;

    $result = $action->handle($flashSaleProduct, [
        'pricing_type' => 'manual',
        'flash_price' => 18000,
        'flash_stock' => 10,
        'status' => 'active',
    ]);

    expect($result->original_price)->toBeNull();
});

test('percent pricing with original_price omitted computes flash_price from the product sell_price', function () {
    $flashSaleProduct = createUpdateTestFlashSaleProductRow();
    $action = new UpdateFlashSaleProductAction;

    $result = $action->handle($flashSaleProduct, [
        'pricing_type' => 'percent',
        'discount_percent' => 20,
        'flash_stock' => 10,
        'status' => 'active',
    ]);

    expect($result->original_price)->toBeNull();
    expect($result->flash_price)->toBe((int) round(15000 * 0.8));
});

test('percent pricing with a marked-up original_price computes flash_price from that markup, not sell_price', function () {
    $flashSaleProduct = createUpdateTestFlashSaleProductRow();
    $action = new UpdateFlashSaleProductAction;

    $result = $action->handle($flashSaleProduct, [
        'pricing_type' => 'percent',
        'discount_percent' => 20,
        'original_price' => 40000,
        'flash_stock' => 10,
        'status' => 'active',
    ]);

    expect($result->original_price)->toBe(40000);
    expect($result->flash_price)->toBe((int) round(40000 * 0.8));
});
