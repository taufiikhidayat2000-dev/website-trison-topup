<?php

namespace App\Jobs;

use App\Enums\DepositStatusEnum;
use App\Models\Wallet\Deposit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireDeposits implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Deposit::query()
            ->where('status', DepositStatusEnum::PENDING)
            ->where('expired_at', '<=', now())
            ->chunkById(100, function ($deposits) {
                foreach ($deposits as $deposit) {
                    $deposit->update([
                        'status' => DepositStatusEnum::EXPIRED,
                    ]);
                }
            });
    }
}
