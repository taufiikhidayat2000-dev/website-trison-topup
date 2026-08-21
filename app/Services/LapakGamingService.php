<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LapakGaming Reseller API client.
 *
 * Endpoints/params verified against the official Postman collection:
 * https://documenter.getpostman.com/view/5668891/2s9XxvTujb
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
                $response = $this->client()->get('/api/balance');

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
     * Get every category (one category = one game/app), including the
     * `forms` (which input fields checkout needs) and `servers` list used
     * to build the PPOBBrand checkout settings.
     */
    public function getCategories(bool $refresh = false): array
    {
        try {
            if ($refresh) {
                Cache::forget($this->prefixCacheKey.'categories');
            }

            return Cache::remember($this->prefixCacheKey.'categories', now()->addDay(), function () {
                $response = $this->client()->get('/api/category');

                if (! $response->successful()) {
                    throw new \Exception('Failed to fetch categories from LapakGaming');
                }

                return $response->json('data.categories', []);
            });
        } catch (\Exception $e) {
            Log::error('LapakGamingService@getCategories: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get all products across every category. Each item has:
     * code, category_code, name, provider_code, price, process_time,
     * country_code, status ('available'|'empty').
     */
    public function getAllProducts(bool $refresh = false): array
    {
        try {
            if ($refresh) {
                Cache::forget($this->prefixCacheKey.'products');
            }

            return Cache::remember($this->prefixCacheKey.'products', now()->addDay(), function () {
                $response = $this->client()->get('/api/all-products');

                if (! $response->successful()) {
                    throw new \Exception('Failed to fetch products from LapakGaming');
                }

                return $response->json('data.products', []);
            });
        } catch (\Exception $e) {
            Log::error('LapakGamingService@getAllProducts: '.$e->getMessage());

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
        ?string $userId = null,
        ?string $serverId = null,
        ?string $partnerReferenceId = null,
        int $quantity = 1,
        ?string $endUserIpAddress = null,
    ): array {
        $body = array_filter([
            'user_id' => $userId,
            'additional_id' => $serverId,
            'count_order' => $quantity,
            'product_code' => $productCode,
            'partner_reference_id' => $partnerReferenceId,
            'end_user_ip_address' => $endUserIpAddress,
        ], fn ($value) => $value !== null && $value !== '');

        if ($callbackUrl = config('lapakgaming.callback_url')) {
            $body['override_callback_url'] = $callbackUrl;
        }

        $response = $this->client()->post('/api/order', $body);

        $json = $response->json() ?? [];
        $successful = $response->successful() && ($json['code'] ?? null) === 'SUCCESS';

        return [
            'successful' => $successful,
            'transaction_id' => $json['data']['tid'] ?? null,
            'message' => $successful ? 'SUCCESS' : ($json['code'] ?? 'Failed to create LapakGaming order'),
            'raw' => $json,
        ];
    }

    /**
     * Check order status by transaction id and/or our partner reference id.
     */
    public function checkOrderStatus(?string $tid = null, ?string $partnerReferenceId = null): array
    {
        $query = array_filter([
            'tid' => $tid,
            'partner_reference_id' => $partnerReferenceId,
        ]);

        $response = $this->client()->get('/api/order_status', $query);

        return $response->json() ?? [];
    }
}
