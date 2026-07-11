<?php

namespace App\Actions\Api\V1\Callback;

use App\Enums\DepositStatusEnum;
use App\Models\Wallet\Deposit;
use App\Services\BalanceService;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Log;

/**
 * Handles Midtrans payment callbacks for member wallet top-ups (deposits).
 * Kept entirely separate from HandleMidtransCallbackAction (which is for
 * order payments) - reuses MidtransService's signature validation as-is,
 * never duplicates or modifies it.
 */
class HandleMidtransDepositCallbackAction
{
    public function __construct(
        public readonly MidtransService $midtransService,
        public readonly BalanceService $balanceService,
    ) {}

    public function handle(array $payload): Deposit
    {
        Log::info('Midtrans Deposit Callback Received', [
            'request' => $payload,
        ]);

        if (! $this->midtransService->validateSignature(
            $payload['order_id'],
            $payload['status_code'],
            $payload['gross_amount'],
            $payload['signature_key'],
        )) {
            throw new \Exception('Invalid signature key', 403);
        }

        $reference = explode('-', $payload['order_id'])[0];
        $deposit = Deposit::where('reference', $reference)->first();

        if (! $deposit) {
            throw new \Exception('Deposit not found', 404);
        }

        // Idempotent: a duplicate/replayed callback must never credit twice,
        // and a late callback for an already-expired/failed deposit must
        // never credit at all.
        if ($deposit->status !== DepositStatusEnum::PENDING) {
            return $deposit;
        }

        $transactionStatus = $payload['transaction_status'] ?? null;

        if (in_array($transactionStatus, ['capture', 'settlement'])) {
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
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $deposit->update([
                'status' => DepositStatusEnum::FAILED,
            ]);
        }

        return $deposit;
    }
}
