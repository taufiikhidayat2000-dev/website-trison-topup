<?php

namespace App\Actions\Cms\Marketing\FlashSale;

use App\Models\FlashSale\FlashSale;
use Illuminate\Support\Facades\Cache;

class DeleteFlashSaleAction
{
    /**
     * Handle the action.
     */
    public function handle(FlashSale $flashSale): bool
    {
        $result = $flashSale->delete();

        Cache::forget('flash_sale:active');

        return $result;
    }
}
