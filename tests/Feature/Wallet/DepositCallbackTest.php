<?php

use App\Actions\Api\V1\Callback\HandleLinkQuDepositCallbackAction;
use App\Actions\Api\V1\Callback\HandleMidtransDepositCallbackAction;
use App\Enums\DepositStatusEnum;
use App\Jobs\ExpireDeposits;
use App\Models\User;
use App\Models\Wallet\BalanceMutation;
use App\Models\Wallet\Deposit;
use Illuminate\Support\Facades\Config;

function createTestDeposit(User $user, array $overrides = []): Deposit
{
    return Deposit::create(array_merge([
        'user_id' => $user->id,
        'reference' => (string) random_int(100000000, 999999999),
        'amount' => 100000,
        'fee' => 4000,
        'total_pay' => 104000,
        'channel' => 'bca',
        'payment_type' => 'bank_transfer',
        'account_number' => '88881234567890',
        'status' => DepositStatusEnum::PENDING,
        'linkqu_reference' => 'trx-'.uniqid(),
        'expired_at' => now()->addHours(24),
    ], $overrides));
}

function signedDepositCallbackPayload(Deposit $deposit, string $status = 'SUCCESS'): array
{
    $payload = [
        'partner_reff' => $deposit->reference,
        'amount' => $deposit->total_pay,
        'va_number' => '88881234567890',
        'username' => 'buyer',
        'status' => $status,
    ];

    $signToString = $payload['partner_reff'].$payload['amount'].$payload['va_number'].$payload['username'];
    $payload['signature'] = hash_hmac('sha256', $signToString, 'test-signature-key');

    return $payload;
}

function signedMidtransDepositCallbackPayload(Deposit $deposit, string $transactionStatus = 'settlement'): array
{
    $statusCode = '200';
    $grossAmount = number_format($deposit->total_pay, 2, '.', '');

    return [
        'order_id' => $deposit->reference,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => $transactionStatus,
        'signature_key' => hash('sha512', $deposit->reference.$statusCode.$grossAmount.'test-midtrans-server-key'),
    ];
}

beforeEach(function () {
    Config::set('linkqu.signature_key', 'test-signature-key');
    Config::set('midtrans.server_key', 'test-midtrans-server-key');
});

test('a successful deposit callback credits the balance exactly once', function () {
    $user = User::factory()->create();
    $deposit = createTestDeposit($user);

    app(HandleLinkQuDepositCallbackAction::class)->handle(signedDepositCallbackPayload($deposit));

    expect($deposit->fresh()->status)->toBe(DepositStatusEnum::PAID);
    expect($user->refresh()->balance)->toBe(100000);
    expect(BalanceMutation::where('reference_type', Deposit::class)->where('reference_id', $deposit->id)->count())->toBe(1);
});

test('a duplicate deposit callback does not credit the balance twice', function () {
    $user = User::factory()->create();
    $deposit = createTestDeposit($user);
    $payload = signedDepositCallbackPayload($deposit);

    app(HandleLinkQuDepositCallbackAction::class)->handle($payload);
    app(HandleLinkQuDepositCallbackAction::class)->handle($payload);

    expect($user->refresh()->balance)->toBe(100000);
    expect(BalanceMutation::where('reference_type', Deposit::class)->where('reference_id', $deposit->id)->count())->toBe(1);
});

test('an expired deposit is never credited, even by a late callback', function () {
    $user = User::factory()->create();
    $deposit = createTestDeposit($user, [
        'expired_at' => now()->subHour(),
    ]);

    (new ExpireDeposits)->handle();

    expect($deposit->fresh()->status)->toBe(DepositStatusEnum::EXPIRED);

    // A late success callback arrives after expiry - must be a no-op.
    app(HandleLinkQuDepositCallbackAction::class)->handle(signedDepositCallbackPayload($deposit));

    expect($deposit->fresh()->status)->toBe(DepositStatusEnum::EXPIRED);
    expect($user->refresh()->balance)->toBe(0);
    expect(BalanceMutation::where('reference_type', Deposit::class)->where('reference_id', $deposit->id)->count())->toBe(0);
});

test('a successful Midtrans deposit callback credits the balance exactly once', function () {
    $user = User::factory()->create();
    $deposit = createTestDeposit($user, ['driver' => 'midtrans']);

    app(HandleMidtransDepositCallbackAction::class)->handle(signedMidtransDepositCallbackPayload($deposit));

    expect($deposit->fresh()->status)->toBe(DepositStatusEnum::PAID);
    expect($user->refresh()->balance)->toBe(100000);
    expect(BalanceMutation::where('reference_type', Deposit::class)->where('reference_id', $deposit->id)->count())->toBe(1);
});

test('a duplicate Midtrans deposit callback does not credit the balance twice', function () {
    $user = User::factory()->create();
    $deposit = createTestDeposit($user, ['driver' => 'midtrans']);
    $payload = signedMidtransDepositCallbackPayload($deposit);

    app(HandleMidtransDepositCallbackAction::class)->handle($payload);
    app(HandleMidtransDepositCallbackAction::class)->handle($payload);

    expect($user->refresh()->balance)->toBe(100000);
    expect(BalanceMutation::where('reference_type', Deposit::class)->where('reference_id', $deposit->id)->count())->toBe(1);
});
