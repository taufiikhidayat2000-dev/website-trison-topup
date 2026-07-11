<?php

namespace App\Actions\Main;

use App\Enums\DepositStatusEnum;
use App\Models\User;
use App\Models\Wallet\Deposit;
use App\Services\LinkQuService;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Log;

/**
 * Creates a member-initiated wallet top-up. Reuses LinkQuService/MidtransService
 * as-is (no signature logic duplicated) and applies the same automatic
 * gateway failover used for order checkout payments.
 */
class CreateDepositAction
{
    public function __construct(
        public readonly LinkQuService $linkQuService,
        public readonly MidtransService $midtransService,
    ) {}

    public function handle(User $user, int $amount, string $channel): Deposit
    {
        $fee = $channel === 'qris'
            ? (int) round($amount * 0.007)
            : 4000;

        $totalPay = $amount + $fee;
        $reference = LinkQuService::generatePartnerReff();
        $paymentType = $channel === 'qris' ? 'qris' : 'bank_transfer';

        $primaryGateway = getSetting('payment_gateway') ?: 'linkqu';
        $fallbackGateway = $primaryGateway === 'linkqu' ? 'midtrans' : 'linkqu';

        $result = null;
        $usedGateway = null;
        $errors = [];

        foreach ([$primaryGateway, $fallbackGateway] as $gateway) {
            try {
                $result = $this->createGatewayDeposit($gateway, $reference, $totalPay, $channel, $user);
                $usedGateway = $gateway;
                break;
            } catch (\Exception $e) {
                $errors[$gateway] = $e->getMessage();
                Log::warning("Deposit gateway [{$gateway}] failed, trying fallback", [
                    'reference' => $reference,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $result) {
            throw new \Exception('Failed to create deposit transaction: '.collect($errors)->map(fn ($message, $gateway) => "{$gateway}: {$message}")->implode(' | '));
        }

        return Deposit::create([
            'user_id' => $user->id,
            'driver' => $usedGateway,
            'reference' => $reference,
            'amount' => $amount,
            'fee' => $fee,
            'total_pay' => $totalPay,
            'channel' => $channel,
            'payment_type' => $paymentType,
            'account_number' => $result['account'],
            'account_code' => $result['code'] ?? null,
            'status' => DepositStatusEnum::PENDING,
            'linkqu_reference' => $result['transaction_id'],
            'expired_at' => now()->addHours(24),
        ]);
    }

    /**
     * Create the gateway transaction on the given gateway ('linkqu' or 'midtrans').
     *
     * @return array{successful: bool, transaction_id: ?string, account: ?string, code: ?string, message: string}
     */
    protected function createGatewayDeposit(string $gateway, string $reference, int $amount, string $channel, User $user): array
    {
        if ($gateway === 'midtrans') {
            $result = $channel === 'qris'
                ? $this->midtransService->createQris(
                    orderId: $reference,
                    amount: $amount,
                )
                : $this->midtransService->createBankTransfer(
                    orderId: $reference,
                    bank: $channel,
                    amount: $amount,
                );

            if (! $result['successful']) {
                throw new \Exception('Failed to create Midtrans deposit: '.$result['message']);
            }

            return $result;
        }

        // Default / fallback: LinkQu
        if ($channel === 'qris') {
            $result = $this->linkQuService->createQris(
                partnerReff: $reference,
                amount: $amount,
                customerId: (string) $user->id,
                customerName: $user->name,
                customerEmail: $user->email,
                customerPhone: $user->phone ?? '',
            );
        } else {
            $bankCode = LinkQuService::BANKS[$channel] ?? null;

            if (! $bankCode) {
                throw new \Exception('Invalid channel. Valid channels are: qris, '.implode(', ', array_keys(LinkQuService::BANKS)));
            }

            $result = $this->linkQuService->createVirtualAccount(
                partnerReff: $reference,
                amount: $amount,
                bankCode: $bankCode,
                customerId: (string) $user->id,
                customerName: $user->name,
                customerEmail: $user->email,
                customerPhone: $user->phone ?? '',
            );
        }

        if (! $result['successful']) {
            throw new \Exception('Failed to create LinkQu deposit: '.$result['message']);
        }

        return $result;
    }
}
