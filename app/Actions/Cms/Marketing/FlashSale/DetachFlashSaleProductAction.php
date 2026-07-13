<?php

namespace App\Actions\Cms\Marketing\FlashSale;

use App\Models\FlashSale\FlashSaleProduct;
use Illuminate\Support\Facades\Cache;

class DetachFlashSaleProductAction
{
    /**
     * Handle the action.
     */
    public function handle(FlashSaleProduct $flashSaleProduct): bool
    {
        $result = $flashSaleProduct->delete();

        Cache::forget('flash_sale:active');

        return $result;
    }
}
