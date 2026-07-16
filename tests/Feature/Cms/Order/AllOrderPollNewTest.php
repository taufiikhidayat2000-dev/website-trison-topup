<?php

use App\Models\Order\Order;
use App\Models\PPOB\PPOBBrand;
use App\Models\PPOB\PPOBCategory;
use App\Models\PPOB\PPOBProduct;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

function createPollNewProduct(string $provider): PPOBProduct
{
    $category = PPOBCategory::create(['name' => 'Poll New Category '.$provider.'-'.uniqid(), 'status' => true]);

    $brand = PPOBBrand::create([
        'p_p_o_b_category_id' => $category->id,
        'name' => 'Poll New Brand '.$provider.'-'.uniqid(),
        'status' => true,
    ]);

    return PPOBProduct::create([
        'p_p_o_b_brand_id' => $brand->id,
        'name' => 'Poll New Product '.$provider.'-'.uniqid(),
        'sku' => 'POLL-SKU-'.uniqid(),
        'provider' => $provider,
        'buy_price' => 10000,
        'sell_price' => 15000,
        'status' => true,
    ]);
}

function createOrderForProduct(PPOBProduct $product): Order
{
    return Order::factory()->create([
        'p_p_o_b_brand_id' => $product->p_p_o_b_brand_id,
        'p_p_o_b_product_id' => $product->id,
    ]);
}

function createUserWithOrderViewPermission(): User
{
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $permission = Permission::firstOrCreate([
        'name' => 'view'.Order::class,
        'guard_name' => 'api',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

test('poll new returns only orders created after the given id, with a correctly computed order_type', function () {
    $user = createUserWithOrderViewPermission();

    $topupProduct = createPollNewProduct('digiflazz');
    $lapakgamingProduct = createPollNewProduct('lapakgaming');
    $giftProduct = createPollNewProduct('gift');
    $manualProduct = createPollNewProduct('manual_topup');

    // Created before the cursor - must NOT be included in the response.
    $baseline = createOrderForProduct($topupProduct);

    $topupOrder = createOrderForProduct($topupProduct);
    $lapakgamingOrder = createOrderForProduct($lapakgamingProduct);
    $giftOrder = createOrderForProduct($giftProduct);
    $manualOrder = createOrderForProduct($manualProduct);

    $response = $this->actingAs($user)->getJson(
        route('cms.order.all-orders.poll-new', ['after_id' => $baseline->id])
    );

    $response->assertOk();

    $orders = collect($response->json('orders'));

    expect($orders)->toHaveCount(4);
    expect($orders->pluck('id')->all())->not->toContain($baseline->id);
    expect($response->json('last_id'))->toBe($manualOrder->id);

    $byId = $orders->keyBy('id');
    expect($byId[$topupOrder->id]['order_type'])->toBe('topup');
    expect($byId[$lapakgamingOrder->id]['order_type'])->toBe('topup');
    expect($byId[$giftOrder->id]['order_type'])->toBe('gift');
    expect($byId[$manualOrder->id]['order_type'])->toBe('manual');
});

test('poll new returns unknown for an order whose product provider is not mapped', function () {
    $user = createUserWithOrderViewPermission();

    $unknownProduct = createPollNewProduct('some_other_provider');
    $baseline = createOrderForProduct($unknownProduct);
    $order = createOrderForProduct($unknownProduct);

    $response = $this->actingAs($user)->getJson(
        route('cms.order.all-orders.poll-new', ['after_id' => $baseline->id])
    );

    $response->assertOk();
    $orders = collect($response->json('orders'))->keyBy('id');

    expect($orders[$order->id]['order_type'])->toBe('unknown');
});

test('poll new is forbidden for an authenticated user without the view order permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('cms.order.all-orders.poll-new'))
        ->assertForbidden();
});

test('poll new redirects guests to the login page', function () {
    $response = $this->get(route('cms.order.all-orders.poll-new'));

    $response->assertRedirect(route('login'));
});
