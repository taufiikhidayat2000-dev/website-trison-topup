<?php

namespace App\Actions\Api\V1\Callback;

use App\Enums\DepositStatusEnum;
use App\Models\Wallet\Deposit;
use App\Services\BalanceService;
use App\Services\LinkQuService;
use Illuminate\Support\Facades\Log;

/**
 * Handles LinkQu payment callbacks for member wallet top-ups (deposits).
 * Kept entirely separate from HandleLinkQuCallbackAction (which is for order
 * payments) - reuses LinkQuService's signature validation as-is, never
 * duplicates or modifies it.
 */
class HandleLinkQuDepositCallbackAction
{
    public function __construct(
        public readonly LinkQuService $linkQuService,
        public readonly BalanceService $balanceService,
    ) {}

    public function handle(array $payload): Deposit
    {
        Log::info('LinkQu Deposit Callback Received', [
            'request' => $payload,
        ]);

        $isValid = isset($payload['va_number'])
            ? $this->linkQuService->validateAccountCallbackSignature($payload)
            : $this->linkQuService->validateGenericCallbackSignature($payload);

        if (! $isValid) {
            throw new \Exception('Invalid signature key', 403);
        }

        $deposit = Deposit::where('reference', $payload['partner_reff'] ?? null)->first();

        if (! $deposit) {
            throw new \Exception('Deposit not found', 404);
        }

        // Idempotent: a duplicate/replayed callback must never credit twice,
        // and a late callback for an already-expired/failed deposit must
        // never credit at all.
        if ($deposit->status !== DepositStatusEnum::PENDING) {
            return $deposit;
        }

        if (($payload['status'] ?? null) === 'SUCCESS') {
            // The credited amount always comes from our own verified deposit
            // record (deposit->amount), never from the raw callback payload.
            $deposit->update([
                'status' => DepositStatusEnum::PAID,
                'paid_at' => now(),
            ]);

            $this->balanceService->credit(
                user: $deposit->user,
                amount: $deposit->amount,
                description: "Top up saldo via {$deposit->channel} (ref: {$deposit->reference})",
                reference: $deposit,
            );
        } elseif (($payload['status'] ?? null) === 'FAILED') {
            $deposit->update([
                'status' => DepositStatusEnum::FAILED,
            ]);
        }

        return $deposit;
    }
}
