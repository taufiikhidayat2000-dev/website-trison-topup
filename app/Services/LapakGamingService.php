<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LapakGaming Reseller API client.
 *
 * NOT LIVE YET: no reseller account/secret key exists for this project.
 * Base URLs and auth (Bearer secret key + IP whitelist) are confirmed from
 * https://documenter.getpostman.com/view/5668891/2s9XxvTujb, but the exact
 * endpoint paths below are our best-effort guess following that same
 * documentation's method names — verify each path against the official
 * Postman collection (or ask LapakGaming support) before enabling.
 */
class LapakGamingService
{
    public string $baseUrl;

    protected string $prefixCacheKey = 'lapakgaming:';

    public function __construct(
        public ?string $secretKey = null,
        public ?bool $isProduction = null,
    ) {
        $this->secretKey = $this->secretKey ?? config('lapakgaming.secret_key');
        $this->isProduction = $this->isProduction ?? config('lapakgaming.is_production', false);
        $this->baseUrl = $this->isProduction ? 'https://www.lapakgaming.com' : 'https://dev.lapakgaming.com';
    }

    public function isEnabled(): bool
    {
        return (bool) config('lapakgaming.enabled') && filled($this->secretKey);
    }

    protected function client()
    {
        return Http::withToken($this->secretKey)->baseUrl($this->baseUrl);
    }

    /**
     * Get the reseller deposit balance.
     */
    public function getBalance(bool $refresh = false): int
    {
        try {
            if ($refresh) {
                Cache::forget($this->prefixCacheKey.'balance');
            }

            return (int) Cache::flexible($this->prefixCacheKey.'balance', [60, 120], function () {
                $response = $this->client()->get('/api/reseller/balance');

                if (! $response->successful()) {
                    throw new \Exception('Failed to fetch balance from LapakGaming');
                }

                return $response->json('data.balance', 0);
            });
        } catch (\Exception $e) {
            Log::error('LapakGamingService@getBalance: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Get all products grouped by category/brand, shaped the same way
     * DigiflazzService::getPrepaidProducts() returns its data so the
     * import controller/action can reuse the same grouping logic.
     */
    public function getAllProducts(bool $refresh = false): array
    {
        try {
            if ($refresh) {
                Cache::forget($this->prefixCacheKey.'products');
            }

            return Cache::remember($this->prefixCacheKey.'products', now()->addDay(), function () {
                $response = $this->client()->get('/api/reseller/products');

                if (! $response->successful()) {
                    throw new \Exception('Failed to fetch products from LapakGaming');
                }

                return $response->json('data', []);
            });
        } catch (\Exception $e) {
            Log::error('LapakGamingService@getAllProducts: '.$e->getMessage());

            return [];
        }
    }

    public function getCategories(): array
    {
        try {
            $response = $this->client()->get('/api/reseller/categories');

            if (! $response->successful()) {
                throw new \Exception('Failed to fetch categories from LapakGaming');
            }

            return $response->json('data', []);
        } catch (\Exception $e) {
            Log::error('LapakGamingService@getCategories: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Create an order (topup) for the given product/target.
     *
     * @return array{successful: bool, transaction_id: ?string, message: string, raw: array}
     */
    public function createOrder(
        string $productCode,
        string $userId,
        ?string $serverId,
        string $partnerReferenceId,
        int $quantity = 1,
        string $countryCode = 'id',
    ): array {
        $response = $this->client()->post('/api/reseller/order/create', [
            'product_code' => $productCode,
            'user_id' => $userId,
            'server_id' => $serverId,
            'quantity' => $quantity,
            'country_code' => $countryCode,
            'partner_reference_id' => $partnerReferenceId,
            'callback_url' => config('lapakgaming.callback_url'),
        ]);

        $json = $response->json() ?? [];

        return [
            'successful' => $response->successful() && ($json['code'] ?? null) === 'SUCCESS',
            'transaction_id' => $json['data']['tid'] ?? null,
            'message' => $json['data']['message'] ?? $json['code'] ?? 'Failed to create LapakGaming order',
            'raw' => $json,
        ];
    }

    /**
     * Check order status by partner reference id.
     */
    public function checkOrderStatus(string $partnerReferenceId): array
    {
        $response = $this->client()->get('/api/reseller/order/status', [
            'partner_reference_id' => $partnerReferenceId,
        ]);

        return $response->json() ?? [];
    }
}
