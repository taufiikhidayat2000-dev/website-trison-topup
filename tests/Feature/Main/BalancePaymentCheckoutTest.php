<?php

use App\Actions\Main\StoreTransactionAction;
use App\Enums\PaymentStatusEnum;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Order\Order;
use App\Models\PPOB\PPOBBrand;
use App\Models\PPOB\PPOBCategory;
use App\Models\PPOB\PPOBProduct;
use App\Models\User;
use App\Models\Wallet\BalanceMutation;
use App\Services\BalanceService;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

function createBalanceCheckoutProduct(int $sellPrice = 25000): PPOBProduct
{
    $category = PPOBCategory::create(['name' => 'Test Category', 'status' => true]);

    $brand = PPOBBrand::create([
        'p_p_o_b_category_id' => $category->id,
        'name' => 'Test Brand',
        'provider' => 'manual_topup',
        'status' => true,
    ]);

    return PPOBProduct::create([
        'p_p_o_b_brand_id' => $brand->id,
        'name' => 'Test Product',
        'sku' => 'TEST-SKU-'.uniqid(),
        'provider' => 'manual_topup',
        'buy_price' => 20000,
        'sell_price' => $sellPrice,
        'status' => true,
    ]);
}

function balanceCheckoutData(PPOBProduct $product): array
{
    return [
        'type' => 'id',
        'account_id' => '12345678',
        'server_id' => null,
        'product_id' => $product->id,
        'p_p_o_b_brand_id' => $product->p_p_o_b_brand_id,
        'p_p_o_b_product_id' => $product->id,
        'email' => 'buyer@example.com',
        'name' => 'Buyer',
        'phone' => '081234567890',
        'payment_type' => 'balance',
        'payment_method' => 'balance',
        'voucher_code' => null,
        'submited' => ['account_id' => '12345678', 'server_id' => null],
    ];
}

beforeEach(function () {
    $this->seed(SettingSeeder::class);
    Mail::fake();
});

test('checkout with sufficient balance succeeds, debits exactly once, and marks the order paid', function () {
    $user = User::factory()->create();
    $user->balance = 100000;
    $user->save();
    $user->refresh(); // pick up the DB-default `is_active` value
    Auth::login($user);

    $product = createBalanceCheckoutProduct(sellPrice: 25000);

    $order = DB::transaction(fn () => app(StoreTransactionAction::class)->handle(balanceCheckoutData($product)));

    expect($order->payment_status)->toBe(PaymentStatusEnum::SETTLEMENT);
    expect($user->refresh()->balance)->toBe(100000 - 25000);

    $mutation = BalanceMutation::where('reference_type', Order::class)
        ->where('reference_id', $order->id)
        ->first();

    expect($mutation)->not->toBeNull();
    expect($mutation->type)->toBe('debit');
    expect($mutation->amount)->toBe(25000);
    expect($mutation->balance_before)->toBe(100000);
    expect($mutation->balance_after)->toBe(75000);

    $this->assertDatabaseHas('payments', [
        'payable_id' => $order->id,
        'payable_type' => Order::class,
        'driver' => 'balance',
    ]);
});

test('checkout with insufficient balance is rejected and creates nothing', function () {
    $user = User::factory()->create();
    $user->balance = 1000;
    $user->save();
    Auth::login($user);

    $product = createBalanceCheckoutProduct(sellPrice: 25000);
    $orderCountBefore = Order::count();

    expect(fn () => DB::transaction(fn () => app(StoreTransactionAction::class)->handle(balanceCheckoutData($product))))
        ->toThrow(Exception::class, 'Saldo tidak mencukupi.');

    expect(Order::count())->toBe($orderCountBefore);
    expect($user->refresh()->balance)->toBe(1000);
});

test('two sequential debits for the same balance cannot double-spend it', function () {
    // A real concurrent race can't be reproduced in a single-process/single-connection
    // test run, but BalanceService::debit() re-validates the balance under a row lock
    // on every single call, so replaying the debit sequentially against a balance that
    // only covers one of the two purchases is the practical proxy here: the first must
    // succeed, the second must be rejected, and the final balance/ledger must reflect
    // exactly one debit - never a negative balance or two mutation rows.
    $user = User::factory()->create();
    $user->balance = 30000;
    $user->save();

    $balanceService = app(BalanceService::class);

    $balanceService->debit($user, 25000, 'First purchase');

    expect(fn () => $balanceService->debit($user, 25000, 'Second purchase (should fail)'))
        ->toThrow(InsufficientBalanceException::class);

    expect($user->refresh()->balance)->toBe(5000);
    expect(BalanceMutation::where('user_id', $user->id)->count())->toBe(1);
});
