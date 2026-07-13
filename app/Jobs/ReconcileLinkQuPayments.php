<?php

namespace App\Jobs;

use App\Actions\Api\V1\Callback\HandleLinkQuCallbackAction;
use App\Models\Payment\Payment;
use App\Services\LinkQuService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ReconcileLinkQuPayments implements ShouldQueue
{
    use Queueable;

    /**
     * Safety net for missed LinkQu callbacks (e.g. the callback URL wasn't registered
     * yet, or the request never arrived): poll the status of any pending, unexpired
     * LinkQu payment and settle it if LinkQu confirms it was actually paid.
     */
    public function handle(LinkQuService $linkQuService, HandleLinkQuCallbackAction $callbackAction): void
    {
        Payment::query()
            ->where('driver', 'linkqu')
            ->whereNull('paid_at')
            ->where('expired_at', '>', now())
            ->chunkById(50, function ($payments) use ($linkQuService, $callbackAction) {
                foreach ($payments as $payment) {
                    $status = $linkQuService->checkStatus($payment->order_id);

                    if (($status['data']['status_paid'] ?? null) !== 'paid') {
                        continue;
                    }

                    // Re-check in case the real callback landed while we were polling.
                    if ($payment->fresh()->paid_at) {
                        continue;
                    }

                    $callbackAction->handlePaymentStatus('SUCCESS', $payment);

                    Log::info('LinkQu payment reconciled via polling fallback', [
                        'payment_id' => $payment->id,
                        'order_id' => $payment->order_id,
                    ]);
                }
            });
    }
}
