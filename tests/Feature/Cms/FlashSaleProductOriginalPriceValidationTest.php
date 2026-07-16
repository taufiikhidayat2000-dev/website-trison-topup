<?php

use App\Http\Requests\Cms\Marketing\FlashSale\AttachFlashSaleProductsRequest;
use App\Http\Requests\Cms\Marketing\FlashSale\UpdateFlashSaleProductRequest;
use App\Models\PPOB\PPOBBrand;
use App\Models\PPOB\PPOBCategory;
use App\Models\PPOB\PPOBProduct;
use Illuminate\Support\Facades\Validator;

/**
 * Regression test for a production division-by-zero crash on the public
 * brand page (BrandController::show): weak validation ('numeric' with no
 * min:1) let original_price = 0 (or a non-numeric string that casts to 0)
 * through, and the price display math divided by it. These tests assert the
 * FormRequest rules reject 0 / non-numeric values before they ever reach the
 * Action/DB layer, for both the attach and update endpoints.
 */
function createValidationTestProduct(): PPOBProduct
{
    $category = PPOBCategory::create(['name' => 'Original Price Validation Category', 'status' => true]);

    $brand = PPOBBrand::create([
        'p_p_o_b_category_id' => $category->id,
        'name' => 'Original Price Validation Brand',
        'status' => true,
    ]);

    return PPOBProduct::create([
        'p_p_o_b_brand_id' => $brand->id,
        'name' => 'Original Price Validation Product',
        'sku' => 'VALIDATION-SKU-'.uniqid(),
        'buy_price' => 10000,
        'sell_price' => 15000,
        'status' => true,
    ]);
}

function attachFlashSaleProductsPayload(PPOBProduct $product, mixed $originalPrice = 'omit'): array
{
    $payload = [
        'product_ids' => [$product->id],
        'pricing_type' => 'manual',
        'flash_price' => 15000,
        'flash_stock' => 10,
    ];

    if ($originalPrice !== 'omit') {
        $payload['original_price'] = $originalPrice;
    }

    return $payload;
}

function updateFlashSaleProductPayload(mixed $originalPrice = 'omit'): array
{
    $payload = [
        'pricing_type' => 'manual',
        'flash_price' => 15000,
        'flash_stock' => 10,
        'status' => 'active',
    ];

    if ($originalPrice !== 'omit') {
        $payload['original_price'] = $originalPrice;
    }

    return $payload;
}

test('attach flash sale products request rejects an original_price of zero', function () {
    $product = createValidationTestProduct();
    $validator = Validator::make(
        attachFlashSaleProductsPayload($product, 0),
        (new AttachFlashSaleProductsRequest)->rules()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('original_price'))->toBeTrue();
});

test('attach flash sale products request rejects a non-numeric original_price', function () {
    $product = createValidationTestProduct();
    $validator = Validator::make(
        attachFlashSaleProductsPayload($product, 'abc'),
        (new AttachFlashSaleProductsRequest)->rules()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('original_price'))->toBeTrue();
});

test('attach flash sale products request accepts a valid original_price', function () {
    $product = createValidationTestProduct();
    $validator = Validator::make(
        attachFlashSaleProductsPayload($product, 1000),
        (new AttachFlashSaleProductsRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('attach flash sale products request accepts an omitted original_price', function () {
    $product = createValidationTestProduct();
    $validator = Validator::make(
        attachFlashSaleProductsPayload($product),
        (new AttachFlashSaleProductsRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('update flash sale product request rejects an original_price of zero', function () {
    $validator = Validator::make(
        updateFlashSaleProductPayload(0),
        (new UpdateFlashSaleProductRequest)->rules()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('original_price'))->toBeTrue();
});

test('update flash sale product request rejects a non-numeric original_price', function () {
    $validator = Validator::make(
        updateFlashSaleProductPayload('abc'),
        (new UpdateFlashSaleProductRequest)->rules()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('original_price'))->toBeTrue();
});

test('update flash sale product request accepts a valid original_price', function () {
    $validator = Validator::make(
        updateFlashSaleProductPayload(1000),
        (new UpdateFlashSaleProductRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('update flash sale product request accepts an omitted original_price', function () {
    $validator = Validator::make(
        updateFlashSaleProductPayload(),
        (new UpdateFlashSaleProductRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

/**
 * original_price now also drives percent-mode pricing (markup-before-discount
 * feature: flash_price = original_price * (1 - discount%)), so the min:1
 * guard must hold under pricing_type=percent too, not just manual.
 */
test('attach flash sale products request rejects an original_price of zero under percent pricing', function () {
    $product = createValidationTestProduct();
    $validator = Validator::make(
        [
            'product_ids' => [$product->id],
            'pricing_type' => 'percent',
            'discount_percent' => 20,
            'original_price' => 0,
            'flash_stock' => 10,
        ],
        (new AttachFlashSaleProductsRequest)->rules()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('original_price'))->toBeTrue();
});

test('attach flash sale products request accepts a valid original_price under percent pricing', function () {
    $product = createValidationTestProduct();
    $validator = Validator::make(
        [
            'product_ids' => [$product->id],
            'pricing_type' => 'percent',
            'discount_percent' => 20,
            'original_price' => 30000,
            'flash_stock' => 10,
        ],
        (new AttachFlashSaleProductsRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});
