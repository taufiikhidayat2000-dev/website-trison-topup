<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\User;
use App\Models\Wallet\BalanceMutation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The only supported way to change a user's balance. Never assign
 * `$user->balance` directly anywhere else in the app — every mutation must
 * go through here so it's always locked, transactional, and ledgered.
 */
class BalanceService
{
    /**
     * Add funds to a user's balance (e.g. deposit paid, refund).
     */
    public function credit(
        User $user,
        int $amount,
        string $description,
        ?Model $reference = null,
        ?User $performedBy = null,
    ): BalanceMutation {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $amount, $description, $reference, $performedBy) {
            /** @var User $locked */
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            $balanceBefore = $locked->balance;
            $balanceAfter = $balanceBefore + $amount;

            $locked->balance = $balanceAfter;
            $locked->save();

            Cache::forget('auth:user:'.$locked->id);

            return BalanceMutation::create([
                'user_id' => $locked->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'performed_by' => $performedBy?->id,
            ]);
        });
    }

    /**
     * Deduct funds from a user's balance (e.g. checkout paid with balance,
     * manual admin deduction). Throws if the balance is insufficient at the
     * moment the row lock is acquired (re-validated, not trusted from the caller).
     */
    public function debit(
        User $user,
        int $amount,
        string $description,
        ?Model $reference = null,
        ?User $performedBy = null,
    ): BalanceMutation {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Debit amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $amount, $description, $reference, $performedBy) {
            /** @var User $locked */
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($locked->balance < $amount) {
                throw new InsufficientBalanceException('Saldo tidak mencukupi.');
            }

            $balanceBefore = $locked->balance;
            $balanceAfter = $balanceBefore - $amount;

            $locked->balance = $balanceAfter;
            $locked->save();

            Cache::forget('auth:user:'.$locked->id);

            return BalanceMutation::create([
                'user_id' => $locked->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'performed_by' => $performedBy?->id,
            ]);
        });
    }
}
