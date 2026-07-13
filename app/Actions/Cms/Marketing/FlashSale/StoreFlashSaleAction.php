<?php

namespace App\Actions\Cms\Marketing\FlashSale;

use App\Models\FlashSale\FlashSale;
use App\Traits\WithMediaCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class StoreFlashSaleAction
{
    use WithMediaCollection;

    /**
     * Handle the action.
     */
    public function handle(array $data): FlashSale
    {
        $flashSale = FlashSale::create($data);

        if (($data['icon_image'] ?? null) instanceof UploadedFile) {
            $this->saveMedia(model: $flashSale, file: $data['icon_image'], collection: 'icon');
        }

        if (($data['banner'] ?? null) instanceof UploadedFile) {
            $this->saveMedia(model: $flashSale, file: $data['banner'], collection: 'banner');
        }

        Cache::forget('flash_sale:active');

        return $flashSale;
    }
}
