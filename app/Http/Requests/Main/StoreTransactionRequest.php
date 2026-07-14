<?php

namespace App\Http\Requests\Main;

use App\Models\PPOB\PPOBProduct;
use App\Services\GameProService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreTransactionRequest extends FormRequest
{
    private ?PPOBProduct $product = null;

    private bool $productResolved = false;

    /**
     * Get the validation rules that apply to the request.
     *
     * The checkout `type` (id / id+server / manual) is never trusted from
     * client input - it's always resolved from the purchased product's
     * brand.settings.type, otherwise a request could set `type=manual` on a
     * brand that actually requires an account_id and skip that requirement
     * entirely, letting an order through with no usable fulfillment data.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'product_id' => 'required|exists:p_p_o_b_products,id',
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'required|numeric',
            'payment_type' => 'required|in:automatic,manual,balance',
            'payment_method' => 'required|string|max:255', // in:qris,bca,bni,bri,mandiri,permata
            'voucher_code' => 'nullable|string|exists:vouchers,code',
        ];

        $checkoutType = $this->checkoutType();

        if ($checkoutType === 'manual') {
            $rules['manual_fields'] = 'nullable|array';

            foreach ($this->manualFields() as $field) {
                $key = $field['key'] ?? null;
                if (! $key) {
                    continue;
                }

                $rules["manual_fields.{$key}"] = (($field['required'] ?? true) ? 'required' : 'nullable').'|string|max:255';
            }
        } else {
            $rules['account_id'] = 'required';
            $rules['server_id'] = $checkoutType === 'id+server' ? 'required' : 'nullable';
        }

        return $rules;
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        if ($this->payment_type === 'balance') {
            if (! auth()->check()) {
                throw ValidationException::withMessages([
                    'payment_type' => 'Anda harus login untuk membayar menggunakan saldo.',
                ]);
            }

            if (! auth()->user()->is_active) {
                throw ValidationException::withMessages([
                    'payment_type' => 'Akun Anda tidak aktif.',
                ]);
            }
        }

        $product = $this->product();
        $checkoutType = $this->checkoutType();

        // Pro Version
        $gameProService = new GameProService;
        // Check the brand if the brand is mobile legends, check the server and uid
        if ($checkoutType !== 'manual' && Str::contains(strtolower($product->brand->name), 'mobile legend')) {
            $resolve = $gameProService->resolveAccount(
                game: 'mobilelegend',
                uid: $this->account_id,
                server: $this->server_id,
            );

            if (! $resolve['status']) {
                throw ValidationException::withMessages([
                    'account_id' => 'Game id or server is invalid',
                ]);
            }
        }

        $submited = $checkoutType === 'manual'
            ? $this->buildManualSubmitedData()
            : [
                'account_id' => $this->account_id,
                'server_id' => $this->server_id ?? null,
            ];

        // Merge additional data
        $this->merge([
            'p_p_o_b_brand_id' => $product->p_p_o_b_brand_id,
            'p_p_o_b_product_id' => $product->id,
            'submited' => $submited,
        ]);
    }

    /**
     * The purchased product (with its brand), memoized so rules(),
     * passedValidation(), and the helpers below share a single query
     * instead of re-fetching the same row several times per request.
     */
    private function product(): ?PPOBProduct
    {
        if (! $this->productResolved) {
            $this->product = PPOBProduct::with('brand')->find($this->product_id);
            $this->productResolved = true;
        }

        return $this->product;
    }

    /**
     * The checkout type actually configured on the purchased product's
     * brand - the only source of truth for which fields are required.
     */
    private function checkoutType(): string
    {
        return $this->product()?->brand?->settings['type'] ?? 'id';
    }

    /**
     * The manual field definitions configured on the purchased product's brand.
     */
    private function manualFields(): array
    {
        return $this->product()?->brand?->settings['manual_fields'] ?? [];
    }

    /**
     * Build the `submited` payload for a "manual" checkout brand from the
     * brand's field definitions. Password-type values are encrypted at rest
     * so game login credentials aren't stored in plaintext. `filled()` (not
     * a truthy check) so a literal "0" value is still encrypted correctly.
     */
    private function buildManualSubmitedData(): array
    {
        $submited = [];

        foreach ($this->manualFields() as $field) {
            $key = $field['key'] ?? null;
            if (! $key) {
                continue;
            }

            $value = $this->input("manual_fields.{$key}");

            $submited[$key] = ($field['type'] ?? null) === 'password' && filled($value)
                ? Crypt::encryptString($value)
                : $value;
        }

        return $submited;
    }
}
