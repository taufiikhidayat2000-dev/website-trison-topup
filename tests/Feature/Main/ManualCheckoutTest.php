<?php

use App\Models\Order\Order;
use App\Models\PPOB\PPOBBrand;
use App\Models\PPOB\PPOBCategory;
use App\Models\PPOB\PPOBProduct;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

function createManualLoginProduct(): PPOBProduct
{
    $category = PPOBCategory::create(['name' => 'Manual Login Category', 'status' => true]);

    $brand = PPOBBrand::create([
        'p_p_o_b_category_id' => $category->id,
        'name' => 'Manual Login Game',
        'provider' => 'manual_topup',
        'status' => true,
        'settings' => [
            'type' => 'manual',
            'manual_fields' => [
                ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true],
                ['key' => 'nickname', 'label' => 'Nickname', 'type' => 'text', 'required' => false],
                ['key' => 'login_via', 'label' => 'Login Via', 'type' => 'select', 'options' => ['Google', 'Facebook', 'Guest'], 'required' => true],
            ],
        ],
    ]);

    return PPOBProduct::create([
        'p_p_o_b_brand_id' => $brand->id,
        'name' => 'Manual Login Product',
        'sku' => 'MANUAL-SKU-'.uniqid(),
        'provider' => 'manual_topup',
        'buy_price' => 20000,
        'sell_price' => 25000,
        'status' => true,
    ]);
}

function manualCheckoutPayload(PPOBProduct $product, array $manualFields = []): array
{
    return [
        'type' => 'manual',
        'manual_fields' => $manualFields,
        'product_id' => $product->id,
        'email' => 'buyer@example.com',
        'name' => 'Buyer',
        'phone' => '081234567890',
        'payment_type' => 'manual',
        'payment_method' => 'bca',
        'voucher_code' => null,
    ];
}

function createIdCheckoutProduct(): PPOBProduct
{
    $category = PPOBCategory::create(['name' => 'ID Checkout Category', 'status' => true]);

    $brand = PPOBBrand::create([
        'p_p_o_b_category_id' => $category->id,
        'name' => 'ID Checkout Game',
        'provider' => 'manual_topup',
        'status' => true,
        'settings' => [
            'type' => 'id',
            'label_id' => 'ID',
        ],
    ]);

    return PPOBProduct::create([
        'p_p_o_b_brand_id' => $brand->id,
        'name' => 'ID Checkout Product',
        'sku' => 'ID-SKU-'.uniqid(),
        'provider' => 'manual_topup',
        'buy_price' => 20000,
        'sell_price' => 25000,
        'status' => true,
    ]);
}

beforeEach(function () {
    $this->seed(SettingSeeder::class);
    Mail::fake();
});

test('manual checkout without required fields fails validation', function () {
    $product = createManualLoginProduct();

    $response = $this->post('/checkout', manualCheckoutPayload($product, [
        'nickname' => 'ProGamer',
    ]));

    $response->assertSessionHasErrors([
        'manual_fields.email',
        'manual_fields.password',
        'manual_fields.login_via',
    ]);
    expect(Order::count())->toBe(0);
});

test('manual checkout stores submitted fields and encrypts the password at rest', function () {
    $product = createManualLoginProduct();

    $response = $this->post('/checkout', manualCheckoutPayload($product, [
        'email' => 'account-owner@example.com',
        'password' => 'SuperSecret123',
        'nickname' => 'ProGamer',
        'login_via' => 'Google',
    ]));

    $response->assertRedirect();
    $order = Order::latest()->first();
    expect($order)->not->toBeNull();

    // The raw password must never be stored in plaintext in the JSON column.
    expect($order->submited['password'])->not->toBe('SuperSecret123');
    expect($order->submited['email'])->toBe('account-owner@example.com');
    expect($order->submited['nickname'])->toBe('ProGamer');
    expect($order->submited['login_via'])->toBe('Google');

    // But it must decrypt back correctly, e.g. for CMS admin display.
    $order->load('brand');
    expect(Crypt::decryptString($order->submited['password']))->toBe('SuperSecret123');
    expect($order->decryptedSubmited()['password'])->toBe('SuperSecret123');
});

test('checkout cannot bypass the account_id requirement by claiming type=manual on a non-manual brand', function () {
    $product = createIdCheckoutProduct();

    // The client claims type=manual (which has no account_id/server_id
    // requirement) even though the purchased brand is actually configured
    // as `id`. The server must derive the real checkout type from the
    // brand itself, not trust the client's `type` field.
    $response = $this->post('/checkout', [
        'type' => 'manual',
        'manual_fields' => [],
        'product_id' => $product->id,
        'email' => 'buyer@example.com',
        'name' => 'Buyer',
        'phone' => '081234567890',
        'payment_type' => 'manual',
        'payment_method' => 'bca',
        'voucher_code' => null,
    ]);

    $response->assertSessionHasErrors(['account_id']);
    expect(Order::count())->toBe(0);
});

test('manual checkout encrypts a literal "0" password instead of storing it in plaintext', function () {
    $product = createManualLoginProduct();

    $this->post('/checkout', manualCheckoutPayload($product, [
        'email' => 'zero-password@example.com',
        'password' => '0',
        'login_via' => 'Google',
    ]))->assertRedirect();

    $order = Order::latest()->first();
    expect($order)->not->toBeNull();
    expect($order->submited['password'])->not->toBe('0');

    $order->load('brand');
    expect(Crypt::decryptString($order->submited['password']))->toBe('0');
});

test('decryptedSubmited still decrypts old orders after the brand renames the password field key', function () {
    $product = createManualLoginProduct();

    $this->post('/checkout', manualCheckoutPayload($product, [
        'email' => 'rename-test@example.com',
        'password' => 'OldFieldKeySecret',
        'login_via' => 'Facebook',
    ]))->assertRedirect();

    $order = Order::latest()->first();
    $brand = $product->brand;

    // Admin later renames the password field's key from "password" to "pwd".
    $settings = $brand->settings;
    $settings['manual_fields'][1]['key'] = 'pwd';
    $brand->update(['settings' => $settings]);

    $order->load('brand');

    // The old order's ciphertext is still stored under the old key "password",
    // which no longer matches any field in the brand's current definition -
    // but decryptedSubmited() must still recognize and decrypt it by shape.
    expect($order->decryptedSubmited()['password'])->toBe('OldFieldKeySecret');
});
