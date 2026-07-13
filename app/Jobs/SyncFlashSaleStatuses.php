<?php

namespace App\Jobs;

use App\Enums\FlashSaleStatusEnum;
use App\Models\FlashSale\FlashSale;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncFlashSaleStatuses implements ShouldQueue
{
    use Queueable;

    /**
     * Auto-transition Flash Sales between Scheduled -> Active -> Ended based on
     * their start/end time, so admins never have to click a button. The
     * after_end_action itself (revert price / hide / sold out / keep showing)
     * is enforced entirely at read-time via FlashSale::purchasable()/
     * visibleOnHomepage() - this job only ever flips status + busts the cache.
     */
    public function handle(): void
    {
        $changed = false;

        FlashSale::query()
            ->where('status', FlashSaleStatusEnum::SCHEDULED)
            ->where('auto_start', true)
            ->where('start_time', '<=', now())
            ->where('end_time', '>', now())
            ->chunkById(50, function ($sales) use (&$changed) {
                foreach ($sales as $sale) {
                    $sale->update(['status' => FlashSaleStatusEnum::ACTIVE]);
                    $changed = true;
                    Log::info('FlashSale activated', ['id' => $sale->id]);
                }
            });

        FlashSale::query()
            ->whereIn('status', [FlashSaleStatusEnum::SCHEDULED, FlashSaleStatusEnum::ACTIVE])
            ->where('auto_end', true)
            ->where('end_time', '<=', now())
            ->chunkById(50, function ($sales) use (&$changed) {
                foreach ($sales as $sale) {
                    $sale->update(['status' => FlashSaleStatusEnum::ENDED, 'ended_at' => now()]);
                    $changed = true;
                    Log::info('FlashSale ended', ['id' => $sale->id, 'action' => $sale->after_end_action->value]);
                }
            });

        if ($changed) {
            Cache::forget('flash_sale:active');
        }
    }
}
