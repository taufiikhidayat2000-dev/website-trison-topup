<?php

namespace App\Actions\Api\V1\Callback;

use App\Models\PPOB\PPOBProduct;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HandleLapakGamingProductCallbackAction
{
    protected string $prefixCacheKey = 'lapakgaming:product-callback-ts:';

    /**
     * @param  array  $payload  Decoded "Product Callback" body, see
     *                          https://documenter.getpostman.com/view/5668891/2s9XxvTujb
     *                          { data: { code, name, provider_code, price, status }, meta: { unix_timestamp } }
     *
     * Only updates products we already imported (via SyncPPOBProductAction) -
     * this webhook has no category/brand context, so it can't create a new
     * PPOBProduct on its own.
     */
    public function handle(array $payload): void
    {
        Log::info('LapakGaming Product Callback Received', ['payload' => $payload]);

        $data = $payload['data'] ?? [];
        $code = $data['code'] ?? null;

        if (! $code) {
            throw new \Exception('Missing data.code in LapakGaming product callback');
        }

        // Callbacks for the same product can arrive out of order/simultaneously;
        // per LapakGaming's docs, meta.unix_timestamp is the flag to compare
        // against so an older callback never overwrites newer data.
        $timestamp = (int) ($payload['meta']['unix_timestamp'] ?? 0);
        $cacheKey = $this->prefixCacheKey.$code;
        $lastTimestamp = (int) Cache::get($cacheKey, 0);

        if ($timestamp > 0 && $timestamp <= $lastTimestamp) {
            return;
        }

        $product = PPOBProduct::where('provider', 'lapakgaming')
            ->where('sku', $code)
            ->first();

        if (! $product) {
            return;
        }

        $price = $data['price'] ?? null;

        $product->update(array_filter([
            'buy_price' => $price,
            'sell_price' => $price !== null ? $price * 1.1 : null,
            'status' => isset($data['status']) ? $data['status'] === 'available' : null,
        ], fn ($value) => $value !== null));

        if ($timestamp > 0) {
            Cache::put($cacheKey, $timestamp, now()->addWeek());
        }
    }
}
