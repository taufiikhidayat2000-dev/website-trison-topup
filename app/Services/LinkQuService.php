<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LinkQuService
{
    public string $baseUrl;

    /**
     * Map of internal bank identifiers (used by the checkout form) to LinkQu bank_code values.
     *
     * @var array<string, string>
     */
    public const BANKS = [
        'bri' => '002',
        'bca' => '014',
        'bni' => '009',
        'mandiri' => '008',
        'permata' => '013',
        'cimb' => '022',
        'danamon' => '011',
        'bsi' => '451',
        'bnc' => '490',
        'ocbc' => '028',
        'muamalat' => '147',
    ];

    /**
     * Map of internal e-wallet identifiers to LinkQu retail_code values.
     *
     * @var array<string, string>
     */
    public const EWALLETS = [
        'ovo' => 'PAYOVO',
        'dana' => 'PAYDANA',
        'linkaja' => 'PAYLINKAJA',
        'shopeepay' => 'PAYSHOPEE',
    ];

    /**
     * Map of internal retail identifiers to LinkQu retail_code values.
     *
     * @var array<string, string>
     */
    public const RETAILS = [
        'alfamart' => 'ALFAMART',
        'indomaret' => 'INDOMARET',
    ];

    public function __construct(
        public ?string $username = null,
        public ?string $pin = null,
        public ?string $clientId = null,
        public ?string $clientSecret = null,
        public ?string $signatureKey = null,
        public ?bool $isProduction = null,
    ) {
        $this->username = $this->username ?? config('linkqu.username');
        $this->pin = $this->pin ?? config('linkqu.pin');
        $this->clientId = $this->clientId ?? config('linkqu.client_id');
        $this->clientSecret = $this->clientSecret ?? config('linkqu.client_secret');
        $this->signatureKey = $this->signatureKey ?? config('linkqu.signature_key');
        $this->isProduction = $this->isProduction ?? config('linkqu.is_production', false);
        $this->baseUrl = $this->isProduction ? 'https://api.linkqu.id' : 'https://gateway-dev.linkqu.id';
    }

    /**
     * Generate a unique, numeric-only partner reference.
     * LinkQu requires partner_reff to be unique and contain only digits.
     */
    public static function generatePartnerReff(): string
    {
        return (string) now()->format('YmdHis').random_int(100, 999);
    }

    /**
     * Build the signature for "create transaction" endpoints (Receive Funds).
     *
     * Formula (per LinkQu signature guide): hash_hmac('sha256', $path.$method.$secondValue, $signatureKey)
     * where $secondValue is the lowercased, alphanumeric-only concatenation of the transaction fields.
     *
     * LinkQu computes the signature using the path without the "/linkqu-partner" prefix,
     * even though the actual request URL includes it. Verified empirically against the
     * production API: signing with the full request path is rejected ("Signature Not Valid!"),
     * while stripping the prefix succeeds.
     */
    protected function buildCreateSignature(string $path, string $method, array $fields): string
    {
        $signaturePath = preg_replace('#^/linkqu-partner#', '', $path);

        $second = strtolower(preg_replace('/[^0-9a-zA-Z]/', '', implode('', $fields)));

        return hash_hmac('sha256', $signaturePath.$method.$second, $this->signatureKey);
    }

    protected function headers(): array
    {
        return [
            'client-id' => $this->clientId,
            'client-secret' => $this->clientSecret,
        ];
    }

    /**
     * Create a Virtual Account (covers BCA, BNI, BRI, Mandiri, Permata, CIMB, etc).
     *
     * @return array{successful: bool, transaction_id: ?string, account: ?string, code: ?string, message: string, raw: array}
     */
    public function createVirtualAccount(
        string $partnerReff,
        int $amount,
        string $bankCode,
        string $customerId,
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        ?string $expiredAt = null,
    ): array {
        $path = '/linkqu-partner/transaction/create/va';
        $expired = $expiredAt ?? now()->addHours(24)->format('YmdHis');

        $signature = $this->buildCreateSignature($path, 'POST', [
            $amount, $expired, $bankCode, $partnerReff, $customerId, $customerName, $customerEmail, $this->clientId,
        ]);

        $response = Http::withHeaders($this->headers())->post($this->baseUrl.$path, [
            'amount' => $amount,
            'partner_reff' => $partnerReff,
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'expired' => $expired,
            'username' => $this->username,
            'pin' => $this->pin,
            'customer_phone' => $customerPhone,
            'customer_email' => $customerEmail,
            'bank_code' => $bankCode,
            'signature' => $signature,
            'url_callback' => config('linkqu.callback_url'),
        ]);

        $json = $response->json() ?? [];

        return [
            'successful' => $response->successful() && ($json['response_code'] ?? null) === '00',
            'transaction_id' => $json['partner_reff2'] ?? null,
            'account' => $json['virtual_account'] ?? null,
            'code' => $bankCode,
            'message' => $json['response_desc'] ?? 'Failed to create virtual account',
            'raw' => $json,
        ];
    }

    /**
     * Create a QRIS payment.
     *
     * @return array{successful: bool, transaction_id: ?string, account: ?string, code: ?string, message: string, raw: array}
     */
    public function createQris(
        string $partnerReff,
        int $amount,
        string $customerId,
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        ?string $expiredAt = null,
    ): array {
        $path = '/linkqu-partner/transaction/create/qris';
        $expired = $expiredAt ?? now()->addHours(24)->format('YmdHis');

        $signature = $this->buildCreateSignature($path, 'POST', [
            $amount, $expired, $partnerReff, $customerId, $customerName, $customerEmail, $this->clientId,
        ]);

        $response = Http::withHeaders($this->headers())->post($this->baseUrl.$path, [
            'amount' => $amount,
            'partner_reff' => $partnerReff,
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'expired' => $expired,
            'username' => $this->username,
            'pin' => $this->pin,
            'customer_phone' => $customerPhone,
            'customer_email' => $customerEmail,
            'signature' => $signature,
            'url_callback' => config('linkqu.callback_url'),
        ]);

        $json = $response->json() ?? [];

        return [
            'successful' => $response->successful() && ($json['response_code'] ?? null) === '00',
            'transaction_id' => $json['partner_reff2'] ?? null,
            'account' => $json['imageqris'] ?? null,
            'code' => $json['qris_text'] ?? null,
            'message' => $json['response_desc'] ?? 'Failed to create QRIS',
            'raw' => $json,
        ];
    }

    /**
     * Create a retail payment code (Alfamart / Indomaret).
     *
     * @return array{successful: bool, transaction_id: ?string, account: ?string, code: ?string, message: string, raw: array}
     */
    public function createRetail(
        string $partnerReff,
        int $amount,
        string $retailCode,
        string $customerId,
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        ?string $expiredAt = null,
    ): array {
        $path = '/linkqu-partner/transaction/create/retail';
        $expired = $expiredAt ?? now()->addHours(24)->format('YmdHis');

        $signature = $this->buildCreateSignature($path, 'POST', [
            $amount, $expired, $retailCode, $partnerReff, $customerId, $customerName, $customerEmail, $this->clientId,
        ]);

        $response = Http::withHeaders($this->headers())->post($this->baseUrl.$path, [
            'amount' => $amount,
            'partner_reff' => $partnerReff,
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'expired' => $expired,
            'username' => $this->username,
            'pin' => $this->pin,
            'retail_code' => $retailCode,
            'customer_phone' => $customerPhone,
            'customer_email' => $customerEmail,
            'signature' => $signature,
            'url_callback' => config('linkqu.callback_url'),
        ]);

        $json = $response->json() ?? [];

        return [
            'successful' => $response->successful() && ($json['response_code'] ?? null) === '00',
            'transaction_id' => $json['partner_reff2'] ?? null,
            'account' => $json['payment_code'] ?? null,
            'code' => $retailCode,
            'message' => $json['response_desc'] ?? 'Failed to create retail payment code',
            'raw' => $json,
        ];
    }

    /**
     * Create an e-wallet payment (DANA, LinkAja, ShopeePay).
     *
     * @return array{successful: bool, transaction_id: ?string, account: ?string, code: ?string, message: string, raw: array}
     */
    public function createEwallet(
        string $partnerReff,
        int $amount,
        string $retailCode,
        string $customerId,
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        ?string $expiredAt = null,
    ): array {
        $path = '/linkqu-partner/transaction/create/paymentewallet';
        $expired = $expiredAt ?? now()->addHours(24)->format('YmdHis');

        $signature = $this->buildCreateSignature($path, 'POST', [
            $amount, $expired, $retailCode, $partnerReff, $customerId, $customerName, $customerEmail, $this->clientId,
        ]);

        $response = Http::withHeaders($this->headers())->post($this->baseUrl.$path, [
            'amount' => $amount,
            'partner_reff' => $partnerReff,
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'expired' => $expired,
            'username' => $this->username,
            'pin' => $this->pin,
            'retail_code' => $retailCode,
            'customer_phone' => $customerPhone,
            'customer_email' => $customerEmail,
            'ewallet_phone' => $customerPhone,
            'bill_title' => 'Payment '.$partnerReff,
            'signature' => $signature,
            'url_callback' => config('linkqu.callback_url'),
        ]);

        $json = $response->json() ?? [];

        return [
            'successful' => $response->successful() && ($json['response_code'] ?? null) === '00',
            'transaction_id' => $json['partner_reff2'] ?? null,
            'account' => $json['url_payment'] ?? $json['payment_code'] ?? null,
            'code' => $retailCode,
            'message' => $json['response_desc'] ?? 'Failed to create e-wallet payment',
            'raw' => $json,
        ];
    }

    /**
     * Check the status of a transaction by partner reference.
     */
    public function checkStatus(string $partnerReff): array
    {
        $response = Http::withHeaders($this->headers())->get($this->baseUrl.'/linkqu-partner/transaction/payment/checkstatus', [
            'username' => $this->username,
            'partnerreff' => $partnerReff,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Validate a "receive funds" callback signature (VA or Retail): partner_reff.amount.va_number.username
     */
    public function validateAccountCallbackSignature(array $payload): bool
    {
        $signToString = ($payload['partner_reff'] ?? '').($payload['amount'] ?? '').($payload['va_number'] ?? '').($payload['username'] ?? '');
        $expected = hash_hmac('sha256', $signToString, $this->signatureKey);

        return hash_equals($expected, $payload['signature'] ?? '');
    }

    /**
     * Validate a "receive funds" callback signature (QRIS / Ewallet / Credit Card): partner_reff.amount.username
     */
    public function validateGenericCallbackSignature(array $payload): bool
    {
        $signToString = ($payload['partner_reff'] ?? '').($payload['amount'] ?? '').($payload['username'] ?? '');
        $expected = hash_hmac('sha256', $signToString, $this->signatureKey);

        return hash_equals($expected, $payload['signature'] ?? '');
    }
}
